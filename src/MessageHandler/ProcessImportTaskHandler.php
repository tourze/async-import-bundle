<?php

namespace AsyncImportBundle\MessageHandler;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Message\ProcessImportBatchMessage;
use AsyncImportBundle\Message\ProcessImportTaskMessage;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use AsyncImportBundle\Service\AsyncImportService;
use AsyncImportBundle\Service\FileParserFactory;
use AsyncImportBundle\Service\ImportHandlerRegistry;
use AsyncImportBundle\Service\ImportProgressTracker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * 处理导入任务的消息处理器
 */
#[AsMessageHandler]
class ProcessImportTaskHandler
{
    public function __construct(
        private readonly AsyncImportTaskRepository $taskRepository,
        private readonly AsyncImportService $importService,
        private readonly FileParserFactory $parserFactory,
        private readonly ImportHandlerRegistry $handlerRegistry,
        private readonly ImportProgressTracker $progressTracker,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ProcessImportTaskMessage $message): void
    {
        $task = $this->taskRepository->find($message->getTaskId());
        
        if (!$task) {
            $this->logger->error('Import task not found', ['taskId' => $message->getTaskId()]);
            return;
        }

        // 检查任务状态
        if (!$this->canProcessTask($task, $message->isRetry())) {
            return;
        }

        try {
            // 更新任务状态为处理中
            $this->importService->updateTaskStatus($task, ImportTaskStatus::PROCESSING);
            
            // 获取文件解析器
            $fileType = $task->getFileType();
            if (!$fileType) {
                $fileType = $this->parserFactory->guessFileType($task->getFile());
                $task->setFileType($fileType);
            }
            
            $parser = $this->parserFactory->getParser($fileType);
            $filePath = $this->importService->getFilePath($task->getFile());
            
            // 验证文件格式
            $validationResult = $parser->validateFormat($filePath);
            if (!$validationResult->isValid()) {
                throw new \RuntimeException('文件格式验证失败: ' . $validationResult->getErrorMessage());
            }
            
            // 获取导入处理器
            $handler = $this->handlerRegistry->getHandler($task->getEntityClass());
            $batchSize = $handler->getBatchSize();
            
            // 开始追踪进度
            $this->progressTracker->startTracking($task);
            
            // 解析文件并分批处理
            $options = $task->getImportConfig() ?? [];
            $rows = [];
            $lineNumber = 0;
            $batchStartLine = 1;
            
            foreach ($parser->parse($filePath, $options) as $row) {
                $lineNumber++;
                $rows[] = $row;
                
                if (count($rows) >= $batchSize) {
                    // 发送批处理消息
                    $batchMessage = new ProcessImportBatchMessage(
                        $task->getId(),
                        $rows,
                        $batchStartLine,
                        $lineNumber
                    );
                    $this->messageBus->dispatch($batchMessage);
                    
                    // 重置批次
                    $rows = [];
                    $batchStartLine = $lineNumber + 1;
                }
            }
            
            // 处理最后一批数据
            if (!empty($rows)) {
                $batchMessage = new ProcessImportBatchMessage(
                    $task->getId(),
                    $rows,
                    $batchStartLine,
                    $lineNumber
                );
                $this->messageBus->dispatch($batchMessage);
            }
            
            $this->logger->info('Import task dispatched for processing', [
                'taskId' => $task->getId(),
                'totalLines' => $lineNumber,
                'batches' => ceil($lineNumber / $batchSize)
            ]);
            
        } catch (\Exception $e) {
            $this->handleTaskError($task, $e, $message->isRetry());
        }
    }

    /**
     * 检查任务是否可以处理
     */
    private function canProcessTask(AsyncImportTask $task, bool $isRetry): bool
    {
        // 如果是重试，检查重试条件
        if ($isRetry) {
            if (!$task->canRetry()) {
                $this->logger->warning('Task cannot be retried', [
                    'taskId' => $task->getId(),
                    'retryCount' => $task->getRetryCount(),
                    'maxRetries' => $task->getMaxRetries()
                ]);
                return false;
            }
            $task->incrementRetryCount();
            $this->entityManager->flush();
        }
        
        // 检查任务状态
        if (!in_array($task->getStatus(), [ImportTaskStatus::PENDING, ImportTaskStatus::FAILED])) {
            $this->logger->warning('Task is not in processable status', [
                'taskId' => $task->getId(),
                'status' => $task->getStatus()->value
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * 处理任务错误
     */
    private function handleTaskError(AsyncImportTask $task, \Exception $e, bool $isRetry): void
    {
        $this->logger->error('Import task processing failed', [
            'taskId' => $task->getId(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        $this->importService->failTask($task, $e->getMessage());
        
        // 如果可以重试，发送重试消息
        if (!$isRetry && $task->canRetry()) {
            $retryMessage = new ProcessImportTaskMessage($task->getId(), true);
            
            // 延迟重试（指数退避）
            $delay = pow(2, $task->getRetryCount()) * 60 * 1000; // 毫秒
            
            $this->messageBus->dispatch($retryMessage, [
                new DelayStamp($delay)
            ]);
            
            $this->logger->info('Task scheduled for retry', [
                'taskId' => $task->getId(),
                'retryCount' => $task->getRetryCount() + 1,
                'delay' => $delay
            ]);
        }
    }
}