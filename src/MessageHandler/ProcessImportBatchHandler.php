<?php

namespace AsyncImportBundle\MessageHandler;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Exception\ImportTaskException;
use AsyncImportBundle\Message\ProcessImportBatchMessage;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use AsyncImportBundle\Service\AsyncImportService;
use AsyncImportBundle\Service\ImportHandlerRegistry;
use AsyncImportBundle\Service\ImportProgressTracker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * 处理导入批次的消息处理器
 */
#[AsMessageHandler]
class ProcessImportBatchHandler
{
    public function __construct(
        private readonly AsyncImportTaskRepository $taskRepository,
        private readonly AsyncImportService $importService,
        private readonly ImportHandlerRegistry $handlerRegistry,
        private readonly ImportProgressTracker $progressTracker,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ProcessImportBatchMessage $message): void
    {
        $task = $this->taskRepository->find($message->getTaskId());
        
        if ($task === null) {
            $this->logger->error('Import task not found', ['taskId' => $message->getTaskId()]);
            return;
        }

        // 检查任务状态
        if ($task->getStatus() !== ImportTaskStatus::PROCESSING) {
            $this->logger->warning('Task is not in processing status', [
                'taskId' => $task->getId(),
                'status' => $task->getStatus()->value
            ]);
            return;
        }

        try {
            // 获取导入处理器
            $handler = $this->handlerRegistry->getHandler($task->getEntityClass());
            
            $successCount = 0;
            $failCount = 0;
            $rows = $message->getRows();
            $lineNumber = $message->getStartLine();
            
            // 处理每一行数据
            foreach ($rows as $row) {
                $processedRow = [];
                
                try {
                    // 预处理数据
                    $processedRow = $handler->preprocess($row);
                    
                    // 验证数据
                    $validationResult = $handler->validate($processedRow, $lineNumber);
                    
                    if (!$validationResult->isValid()) {
                        throw new ImportTaskException($validationResult->getErrorMessage());
                    }
                    
                    // 导入数据
                    $handler->import($processedRow, $task);
                    $successCount++;
                    
                    // 记录警告信息
                    if ($validationResult->hasWarnings()) {
                        foreach ($validationResult->getWarnings() as $warning) {
                            $this->logger->warning('Import warning', [
                                'taskId' => $task->getId(),
                                'line' => $lineNumber,
                                'warning' => $warning
                            ]);
                        }
                    }
                    
                } catch (\Exception $e) {
                    $failCount++;
                    
                    // 记录错误日志
                    $this->importService->logError(
                        $task,
                        $lineNumber,
                        $e->getMessage(),
                        $row,
                        $processedRow
                    );
                    
                    $this->logger->error('Failed to import row', [
                        'taskId' => $task->getId(),
                        'line' => $lineNumber,
                        'error' => $e->getMessage()
                    ]);
                }
                
                $lineNumber++;
            }
            
            // 提交数据库事务
            $this->entityManager->flush();
            
            // 更新进度
            $currentProcessed = $task->getProcessCount() + $message->getRowCount();
            $this->importService->updateProgress($task, $currentProcessed, $successCount, $failCount);
            $this->progressTracker->updateProgress($task, $currentProcessed, 
                $task->getSuccessCount(), $task->getFailCount());
            
            // 检查是否已处理完所有数据
            if ($currentProcessed >= $task->getTotalCount()) {
                $this->completeTask($task);
            }
            
            $this->logger->info('Batch processed successfully', [
                'taskId' => $task->getId(),
                'startLine' => $message->getStartLine(),
                'endLine' => $message->getEndLine(),
                'success' => $successCount,
                'failed' => $failCount
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Batch processing failed', [
                'taskId' => $task->getId(),
                'startLine' => $message->getStartLine(),
                'endLine' => $message->getEndLine(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 标记任务失败
            $this->importService->failTask($task, 
                sprintf('批处理失败 (行 %d-%d): %s', 
                    $message->getStartLine(), 
                    $message->getEndLine(), 
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * 完成任务
     */
    private function completeTask(AsyncImportTask $task): void
    {
        // 停止进度追踪
        $stats = $this->progressTracker->stopTracking($task);
        
        // 标记任务完成
        $this->importService->completeTask($task);
        
        // 记录完成信息
        $this->logger->info('Import task completed', [
            'taskId' => $task->getId(),
            'totalProcessed' => $task->getProcessCount(),
            'successCount' => $task->getSuccessCount(),
            'failCount' => $task->getFailCount(),
            'duration' => $stats['duration'] ?? 0,
            'averageSpeed' => $stats['averageSpeed'] ?? 0
        ]);
    }
}