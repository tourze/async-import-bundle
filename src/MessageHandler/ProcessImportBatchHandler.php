<?php

namespace AsyncImportBundle\MessageHandler;

use AsyncImportBundle\DTO\ValidationResult;
use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Exception\ImportTaskException;
use AsyncImportBundle\Message\ProcessImportBatchMessage;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use AsyncImportBundle\Service\AsyncImportService;
use AsyncImportBundle\Service\ImportHandlerInterface;
use AsyncImportBundle\Service\ImportHandlerRegistry;
use AsyncImportBundle\Service\ImportProgressTracker;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * 处理导入批次的消息处理器
 */
#[AsMessageHandler]
#[WithMonologChannel(channel: 'async_import')]
readonly class ProcessImportBatchHandler
{
    public function __construct(
        private AsyncImportTaskRepository $taskRepository,
        private AsyncImportService $importService,
        private ImportHandlerRegistry $handlerRegistry,
        private ImportProgressTracker $progressTracker,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessImportBatchMessage $message): void
    {
        $task = $this->findAndValidateTask($message->getTaskId());
        if (null === $task) {
            return;
        }

        if (!$this->isTaskInValidStatus($task)) {
            return;
        }

        try {
            $this->processBatch($task, $message);
        } catch (\Exception $e) {
            $this->handleBatchProcessingFailure($task, $message, $e);
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
            'averageSpeed' => $stats['averageSpeed'] ?? 0,
        ]);
    }

    private function findAndValidateTask(string $taskId): ?AsyncImportTask
    {
        $task = $this->taskRepository->find($taskId);

        if (null === $task) {
            $this->logger->error('Import task not found', ['taskId' => $taskId]);
        }

        return $task;
    }

    private function isTaskInValidStatus(AsyncImportTask $task): bool
    {
        if (ImportTaskStatus::PROCESSING !== $task->getStatus()) {
            $this->logger->warning('Task is not in processing status', [
                'taskId' => $task->getId(),
                'status' => $task->getStatus()->value,
            ]);

            return false;
        }

        return true;
    }

    private function processBatch(AsyncImportTask $task, ProcessImportBatchMessage $message): void
    {
        $entityClass = $task->getEntityClass();
        if (null === $entityClass) {
            throw new ImportTaskException('Task entity class is null');
        }

        $handler = $this->handlerRegistry->getHandler($entityClass);
        $processingResult = $this->processRows($task, $message, $handler);

        $this->entityManager->flush();
        $this->updateTaskProgress($task, $message, $processingResult);
        $this->logBatchSuccess($task, $message, $processingResult);
    }

    /**
     * @return array{success: int, fail: int}
     */
    private function processRows(AsyncImportTask $task, ProcessImportBatchMessage $message, ImportHandlerInterface $handler): array
    {
        $successCount = 0;
        $failCount = 0;
        $rows = $message->getRows();
        $lineNumber = $message->getStartLine();

        foreach ($rows as $row) {
            $result = $this->processRow($task, $handler, $row, $lineNumber);
            if ($result['success']) {
                ++$successCount;
            } else {
                ++$failCount;
            }
            ++$lineNumber;
        }

        return ['success' => $successCount, 'fail' => $failCount];
    }

    /**
     * @param array<string, mixed> $row
     * @return array{success: bool, row?: array<string, mixed>}
     */
    private function processRow(AsyncImportTask $task, ImportHandlerInterface $handler, array $row, int $lineNumber): array
    {
        $processedRow = [];

        try {
            $processedRow = $handler->preprocess($row);
            $validationResult = $handler->validate($processedRow, $lineNumber);

            if (!$validationResult->isValid()) {
                throw new ImportTaskException($validationResult->getErrorMessage());
            }

            $handler->import($processedRow, $task);
            $this->logWarnings($task, $validationResult, $lineNumber);

            return ['success' => true];
        } catch (\Exception $e) {
            $this->handleRowProcessingError($task, $e, $lineNumber, $row, $processedRow);

            return ['success' => false];
        }
    }

    private function logWarnings(AsyncImportTask $task, ValidationResult $validationResult, int $lineNumber): void
    {
        if ($validationResult->hasWarnings()) {
            foreach ($validationResult->getWarnings() as $warning) {
                $this->logger->warning('Import warning', [
                    'taskId' => $task->getId(),
                    'line' => $lineNumber,
                    'warning' => $warning,
                ]);
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $processedRow
     */
    private function handleRowProcessingError(AsyncImportTask $task, \Exception $e, int $lineNumber, array $row, array $processedRow): void
    {
        $this->importService->logError($task, $lineNumber, $e->getMessage(), $row, $processedRow);

        $this->logger->error('Failed to import row', [
            'taskId' => $task->getId(),
            'line' => $lineNumber,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * @param array{success: int, fail: int} $processingResult
     */
    private function updateTaskProgress(AsyncImportTask $task, ProcessImportBatchMessage $message, array $processingResult): void
    {
        $currentProcessed = ($task->getProcessCount() ?? 0) + $message->getRowCount();
        $this->importService->updateProgress($task, $currentProcessed, $processingResult['success'], $processingResult['fail']);
        $this->progressTracker->updateProgress($task, $currentProcessed, $task->getSuccessCount(), $task->getFailCount());

        if ($currentProcessed >= $task->getTotalCount()) {
            $this->completeTask($task);
        }
    }

    /**
     * @param array{success: int, fail: int} $processingResult
     */
    private function logBatchSuccess(AsyncImportTask $task, ProcessImportBatchMessage $message, array $processingResult): void
    {
        $this->logger->info('Batch processed successfully', [
            'taskId' => $task->getId(),
            'startLine' => $message->getStartLine(),
            'endLine' => $message->getEndLine(),
            'success' => $processingResult['success'],
            'failed' => $processingResult['fail'],
        ]);
    }

    private function handleBatchProcessingFailure(AsyncImportTask $task, ProcessImportBatchMessage $message, \Exception $e): void
    {
        $this->logger->error('Batch processing failed', [
            'taskId' => $task->getId(),
            'startLine' => $message->getStartLine(),
            'endLine' => $message->getEndLine(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->importService->failTask(
            $task,
            sprintf(
                '批处理失败 (行 %d-%d): %s',
                $message->getStartLine(),
                $message->getEndLine(),
                $e->getMessage()
            )
        );
    }
}
