<?php

namespace AsyncImportBundle\Service;

use AsyncImportBundle\Entity\AsyncImportErrorLog;
use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Repository\AsyncImportErrorLogRepository;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * 异步导入主服务
 */
class AsyncImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AsyncImportTaskRepository $taskRepository,
        private readonly AsyncImportErrorLogRepository $errorLogRepository,
        private readonly FileParserFactory $parserFactory,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
        private readonly string $uploadDirectory
    ) {
    }

    /**
     * 创建导入任务
     */
    public function createTask(
        UploadedFile $file,
        string $entityClass,
        array $options = []
    ): AsyncImportTask {
        // 保存上传的文件
        $fileName = $this->saveUploadedFile($file);
        
        // 创建任务实体
        $task = new AsyncImportTask();
        $task->setFile($fileName);
        $task->setEntityClass($entityClass);
        $user = $this->security->getUser();
        if ($user !== null) {
            $task->setUserId($user->getUserIdentifier());
        }
        $task->setStatus(ImportTaskStatus::PENDING);
        $task->setImportConfig($options);
        
        // 尝试猜测文件类型
        try {
            $fileType = $this->parserFactory->guessFileType($fileName);
            $task->setFileType($fileType);
            
            // 获取总行数
            $parser = $this->parserFactory->getParser($fileType);
            $totalCount = $parser->countRows($this->getFilePath($fileName), $options);
            $task->setTotalCount($totalCount);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to guess file type or count rows', [
                'file' => $fileName,
                'error' => $e->getMessage()
            ]);
        }
        
        if (isset($options['priority'])) {
            $task->setPriority((int) $options['priority']);
        }
        
        if (isset($options['maxRetries'])) {
            $task->setMaxRetries((int) $options['maxRetries']);
        }
        
        if (isset($options['remark'])) {
            $task->setRemark($options['remark']);
        }
        
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        
        $this->logger->info('Import task created', [
            'taskId' => $task->getId(),
            'file' => $fileName,
            'entityClass' => $entityClass
        ]);
        
        return $task;
    }

    /**
     * 保存上传的文件
     */
    private function saveUploadedFile(UploadedFile $file): string
    {
        $fileName = sprintf(
            '%s_%s.%s',
            date('YmdHis'),
            uniqid(),
            $file->guessExtension() ?: $file->getClientOriginalExtension()
        );

        $file->move($this->uploadDirectory, $fileName);

        return $fileName;
    }

    /**
     * 获取文件完整路径
     */
    public function getFilePath(string $fileName): string
    {
        return $this->uploadDirectory . '/' . $fileName;
    }

    /**
     * 记录错误日志
     */
    public function logError(
        AsyncImportTask $task,
        int $lineNumber,
        string $error,
        array $rawData = [],
        array $processedData = []
    ): void {
        $errorLog = new AsyncImportErrorLog();
        $errorLog->setTaskId($task->getId());
        $errorLog->setEntityClass($task->getEntityClass());
        $errorLog->setLine($lineNumber);
        $errorLog->setError($error);
        $errorLog->setRawRow($rawData);
        $errorLog->setNewRow($processedData);

        $this->entityManager->persist($errorLog);

        // 更新任务的错误信息
        $task->setLastErrorMessage($error);
        $task->setLastErrorTime(new \DateTimeImmutable());
        $task->setFailCount($task->getFailCount() + 1);

        $this->entityManager->flush();
    }

    /**
     * 更新任务进度
     */
    public function updateProgress(AsyncImportTask $task, int $processed, int $success = 0, int $failed = 0): void
    {
        $task->setProcessCount($processed);

        if ($success > 0) {
            $task->setSuccessCount($task->getSuccessCount() + $success);
        }

        if ($failed > 0) {
            $task->setFailCount($task->getFailCount() + $failed);
        }

        $task->setMemoryUsage(memory_get_usage(true));

        $this->entityManager->flush();
    }

    /**
     * 标记任务完成
     */
    public function completeTask(AsyncImportTask $task): void
    {
        $status = $task->getFailCount() > 0 ? ImportTaskStatus::COMPLETED : ImportTaskStatus::COMPLETED;
        $this->updateTaskStatus($task, $status);

        $this->logger->info('Import task completed', [
            'taskId' => $task->getId(),
            'totalCount' => $task->getTotalCount(),
            'successCount' => $task->getSuccessCount(),
            'failCount' => $task->getFailCount(),
            'duration' => $task->getStartTime() !== null && $task->getEndTime() !== null
                ? $task->getEndTime()->getTimestamp() - $task->getStartTime()->getTimestamp()
                : 0
        ]);
    }

    /**
     * 更新任务状态
     */
    public function updateTaskStatus(AsyncImportTask $task, ImportTaskStatus $status): void
    {
        $task->setStatus($status);

        if ($status === ImportTaskStatus::PROCESSING && $task->getStartTime() === null) {
            $task->setStartTime(new \DateTimeImmutable());
        }

        if (in_array($status, [ImportTaskStatus::COMPLETED, ImportTaskStatus::FAILED, ImportTaskStatus::CANCELLED])) {
            $task->setEndTime(new \DateTimeImmutable());
        }

        $this->entityManager->flush();
    }

    /**
     * 标记任务失败
     */
    public function failTask(AsyncImportTask $task, string $error): void
    {
        $task->setLastErrorMessage($error);
        $task->setLastErrorTime(new \DateTimeImmutable());
        $this->updateTaskStatus($task, ImportTaskStatus::FAILED);

        $this->logger->error('Import task failed', [
            'taskId' => $task->getId(),
            'error' => $error
        ]);
    }

    /**
     * 获取任务错误日志
     */
    public function getTaskErrors(AsyncImportTask $task, ?int $limit = null, ?int $offset = null): array
    {
        return $this->errorLogRepository->findByTaskId($task->getId(), $limit, $offset);
    }

    /**
     * 清理过期任务和文件
     */
    public function cleanupOldTasks(int $daysToKeep = 30): int
    {
        $deleteBefore = new \DateTime("-{$daysToKeep} days");
        $tasks = $this->taskRepository->findOldTasks($deleteBefore);

        $count = 0;
        foreach ($tasks as $task) {
            // 删除文件
            $filePath = $this->getFilePath($task->getFile());
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // 删除错误日志
            $this->errorLogRepository->deleteByTaskId($task->getId());

            // 删除任务
            $this->entityManager->remove($task);
            $count++;
        }

        $this->entityManager->flush();

        $this->logger->info('Cleaned up old import tasks', [
            'count' => $count,
            'daysToKeep' => $daysToKeep
        ]);

        return $count;
    }
}