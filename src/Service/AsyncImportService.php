<?php

namespace AsyncImportBundle\Service;

use AsyncImportBundle\Entity\AsyncImportErrorLog;
use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Exception\FileOperationException;
use AsyncImportBundle\Exception\FileSecurityException;
use AsyncImportBundle\Repository\AsyncImportErrorLogRepository;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * 异步导入主服务
 */
readonly class AsyncImportService
{
    /**
     * 允许的文件扩展名白名单
     *
     * @var array<string>
     */
    private const ALLOWED_EXTENSIONS = ['csv', 'xls', 'xlsx', 'json'];

    /**
     * 最大文件名长度
     */
    private const MAX_FILENAME_LENGTH = 255;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AsyncImportTaskRepository $taskRepository,
        private AsyncImportErrorLogRepository $errorLogRepository,
        private FileParserFactory $parserFactory,
        private Security $security,
        private LoggerInterface $logger,
        #[Autowire(param: '%async_import.upload_dir%')] private string $uploadDirectory,
    ) {
    }

    /**
     * 创建导入任务
     *
     * @param array<string, mixed> $options
     */
    public function createTask(
        UploadedFile $file,
        string $entityClass,
        array $options = [],
    ): AsyncImportTask {
        // 保存上传的文件
        $fileName = $this->saveUploadedFile($file);

        // 创建任务实体
        $task = new AsyncImportTask();
        $task->setFile($fileName);
        $task->setEntityClass($entityClass);
        $user = $this->security->getUser();
        if (null !== $user) {
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
                'error' => $e->getMessage(),
            ]);
        }

        if (isset($options['priority']) && is_numeric($options['priority'])) {
            $task->setPriority((int) $options['priority']);
        }

        if (isset($options['maxRetries']) && is_numeric($options['maxRetries'])) {
            $task->setMaxRetries((int) $options['maxRetries']);
        }

        if (isset($options['remark'])) {
            $remark = $options['remark'];
            $task->setRemark(is_string($remark) ? $remark : null);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->logger->info('Import task created', [
            'taskId' => $task->getId(),
            'file' => $fileName,
            'entityClass' => $entityClass,
        ]);

        return $task;
    }

    /**
     * 保存上传的文件
     *
     * @throws FileSecurityException 当文件不安全时抛出异常
     */
    private function saveUploadedFile(UploadedFile $file): string
    {
        // 验证文件扩展名
        $originalExtension = strtolower($file->getClientOriginalExtension());
        if (!in_array($originalExtension, self::ALLOWED_EXTENSIONS, true)) {
            throw new FileSecurityException(sprintf('不允许的文件扩展名: %s，允许的扩展名: %s', $originalExtension, implode(', ', self::ALLOWED_EXTENSIONS)));
        }

        // 验证文件大小和类型（额外安全检查）
        if (0 === $file->getSize()) {
            throw new FileSecurityException('上传的文件为空');
        }

        // 生成安全的文件名（不使用原始文件名，避免路径遍历攻击）
        $fileName = sprintf(
            '%s_%s.%s',
            date('YmdHis'),
            bin2hex(random_bytes(8)), // 使用加密安全的随机字符串替代uniqid
            $originalExtension
        );

        // 验证生成的文件名长度
        if (strlen($fileName) > self::MAX_FILENAME_LENGTH) {
            throw new FileSecurityException('生成的文件名过长');
        }

        // 确保上传目录安全
        $this->ensureUploadDirectorySecurity();

        // 移动文件到安全目录
        $file->move($this->uploadDirectory, $fileName);

        $this->logger->info('文件上传成功', [
            'fileName' => $fileName,
            'originalName' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mimeType' => $file->getClientMimeType(),
        ]);

        return $fileName;
    }

    /**
     * 确保上传目录安全
     *
     * @throws FileOperationException 当目录不安全时抛出异常
     */
    private function ensureUploadDirectorySecurity(): void
    {
        // 获取规范化的上传目录路径
        $realUploadDir = realpath($this->uploadDirectory);
        if (false === $realUploadDir) {
            throw new FileOperationException('上传目录不存在或无法访问: ' . $this->uploadDirectory);
        }

        // 检查目录权限
        if (!is_writable($realUploadDir)) {
            throw new FileOperationException('上传目录不可写: ' . $realUploadDir);
        }
    }

    /**
     * 获取文件完整路径（安全版本）
     *
     * @throws FileSecurityException 当文件名不安全时抛出异常
     */
    public function getFilePath(string $fileName): string
    {
        // 验证文件名安全性
        if (!$this->isFileNameSafe($fileName)) {
            throw new FileSecurityException('不安全的文件名: ' . $fileName);
        }

        $filePath = $this->uploadDirectory . '/' . $fileName;

        // 验证路径安全性
        if (!$this->isFilePathSafe($filePath)) {
            throw new FileSecurityException('不安全的文件路径: ' . $filePath);
        }

        return $filePath;
    }

    /**
     * 验证文件名是否安全
     */
    private function isFileNameSafe(string $fileName): bool
    {
        // 检查文件名长度
        if (0 === strlen($fileName) || strlen($fileName) > self::MAX_FILENAME_LENGTH) {
            return false;
        }

        // 检查危险字符和路径遍历
        $dangerousPatterns = [
            '..', '/', '\\', '<', '>', ':', '"', '|', '?', '*',
            "\0", "\r", "\n", "\t",
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (str_contains($fileName, $pattern)) {
                return false;
            }
        }

        // 检查是否以点开头（隐藏文件）
        if (str_starts_with($fileName, '.')) {
            return false;
        }

        // 验证扩展名
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return false;
        }

        return true;
    }

    /**
     * 验证文件路径是否在允许的目录内
     */
    private function isFilePathSafe(string $filePath): bool
    {
        // 获取规范化路径
        $realFilePath = realpath($filePath);
        $realUploadDir = realpath($this->uploadDirectory);

        // 如果文件不存在，检查父目录
        if (false === $realFilePath) {
            $realFilePath = realpath(dirname($filePath));
            if (false === $realFilePath) {
                return false;
            }
            $realFilePath = $realFilePath . '/' . basename($filePath);
        }

        if (false === $realUploadDir) {
            return false;
        }

        // 确保文件路径在上传目录内
        return str_starts_with($realFilePath, $realUploadDir . DIRECTORY_SEPARATOR);
    }

    /**
     * 记录错误日志
     *
     * @param array<string, mixed> $rawData
     * @param array<string, mixed> $processedData
     */
    public function logError(
        AsyncImportTask $task,
        int $lineNumber,
        string $error,
        array $rawData = [],
        array $processedData = [],
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
        $status = ImportTaskStatus::COMPLETED;
        $this->updateTaskStatus($task, $status);

        $this->logger->info('Import task completed', [
            'taskId' => $task->getId(),
            'totalCount' => $task->getTotalCount(),
            'successCount' => $task->getSuccessCount(),
            'failCount' => $task->getFailCount(),
            'duration' => null !== $task->getStartTime() && null !== $task->getEndTime()
                ? $task->getEndTime()->getTimestamp() - $task->getStartTime()->getTimestamp()
                : 0,
        ]);
    }

    /**
     * 更新任务状态
     */
    public function updateTaskStatus(AsyncImportTask $task, ImportTaskStatus $status): void
    {
        $task->setStatus($status);

        if (ImportTaskStatus::PROCESSING === $status && null === $task->getStartTime()) {
            $task->setStartTime(new \DateTimeImmutable());
        }

        if (in_array($status, [ImportTaskStatus::COMPLETED, ImportTaskStatus::FAILED, ImportTaskStatus::CANCELLED], true)) {
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
            'error' => $error,
        ]);
    }

    /**
     * 获取任务错误日志
     *
     * @return AsyncImportErrorLog[]
     */
    public function getTaskErrors(AsyncImportTask $task, ?int $limit = null, ?int $offset = null): array
    {
        $taskId = $task->getId();
        if (null === $taskId) {
            return [];
        }

        return $this->errorLogRepository->findByTaskId($taskId, $limit, $offset);
    }

    /**
     * 清理过期任务和文件（安全版本）
     */
    public function cleanupOldTasks(int $daysToKeep = 30): int
    {
        if ($daysToKeep <= 0) {
            throw new FileSecurityException('保留天数必须大于0');
        }

        $deleteBefore = new \DateTime("-{$daysToKeep} days");
        $tasks = $this->taskRepository->findOldTasks($deleteBefore);

        $count = 0;
        $failedDeletions = [];

        foreach ($tasks as $task) {
            $taskId = $task->getId();
            $fileName = $task->getFile();

            try {
                // 安全删除文件
                if (null !== $fileName) {
                    $this->safeDeleteFile($fileName);
                }

                // 删除错误日志
                if (null !== $taskId) {
                    $this->errorLogRepository->deleteByTaskId($taskId);
                }

                // 删除任务实体
                $this->entityManager->remove($task);
                ++$count;
            } catch (\Exception $e) {
                $failedDeletions[] = [
                    'taskId' => $taskId,
                    'fileName' => $fileName,
                    'error' => $e->getMessage(),
                ];

                $this->logger->warning('删除过期任务失败', [
                    'taskId' => $taskId,
                    'fileName' => $fileName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->entityManager->flush();

        $this->logger->info('清理过期导入任务完成', [
            'count' => $count,
            'daysToKeep' => $daysToKeep,
            'failedDeletions' => count($failedDeletions),
        ]);

        if ([] !== $failedDeletions) {
            $this->logger->error('部分文件删除失败', ['failures' => $failedDeletions]);
        }

        return $count;
    }

    /**
     * 安全删除文件
     *
     * @throws FileOperationException 当删除失败时抛出异常
     */
    private function safeDeleteFile(string $fileName): void
    {
        // 使用安全的路径获取方法
        $filePath = $this->getFilePath($fileName);

        // 检查文件是否存在
        if (!file_exists($filePath)) {
            return; // 文件不存在，认为删除成功
        }

        // 检查文件是否可删除
        if (!is_writable($filePath)) {
            throw new FileOperationException('文件不可删除（权限不足）: ' . $fileName);
        }

        // 双重确认文件路径安全性
        if (!$this->isFilePathSafe($filePath)) {
            throw new FileOperationException('拒绝删除不安全路径的文件: ' . $fileName);
        }

        // 删除文件
        $success = unlink($filePath);
        if (!$success) {
            throw new FileOperationException('文件删除失败: ' . $fileName);
        }

        $this->logger->debug('文件删除成功', ['fileName' => $fileName]);
    }

    /**
     * 计算任务进度百分比
     */
    public function getTaskProgressPercentage(AsyncImportTask $task): float
    {
        $totalCount = $task->getTotalCount();
        if (0 === $totalCount || null === $totalCount) {
            return 0.0;
        }

        return round((($task->getProcessCount() ?? 0) / $totalCount) * 100, 2);
    }

    /**
     * 检查任务是否可以重试
     */
    public function canTaskRetry(AsyncImportTask $task): bool
    {
        return ImportTaskStatus::FAILED === $task->getStatus()
            && $task->getRetryCount() < $task->getMaxRetries();
    }

    /**
     * 增加任务重试次数
     */
    public function incrementTaskRetryCount(AsyncImportTask $task): void
    {
        $task->setRetryCount($task->getRetryCount() + 1);
        $this->entityManager->flush();
    }
}
