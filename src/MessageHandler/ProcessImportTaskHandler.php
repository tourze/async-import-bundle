<?php

namespace AsyncImportBundle\MessageHandler;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Exception\ImportTaskException;
use AsyncImportBundle\Message\ProcessImportBatchMessage;
use AsyncImportBundle\Message\ProcessImportTaskMessage;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use AsyncImportBundle\Service\AsyncImportService;
use AsyncImportBundle\Service\FileParserFactory;
use AsyncImportBundle\Service\FileParserInterface;
use AsyncImportBundle\Service\ImportHandlerInterface;
use AsyncImportBundle\Service\ImportHandlerRegistry;
use AsyncImportBundle\Service\ImportProgressTracker;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * 处理导入任务的消息处理器
 */
#[AsMessageHandler]
#[WithMonologChannel(channel: 'async_import')]
readonly class ProcessImportTaskHandler
{
    public function __construct(
        private AsyncImportTaskRepository $taskRepository,
        private AsyncImportService $importService,
        private FileParserFactory $parserFactory,
        private ImportHandlerRegistry $handlerRegistry,
        private ImportProgressTracker $progressTracker,
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessImportTaskMessage $message): void
    {
        $task = $this->findTask($message->getTaskId());
        if (null === $task) {
            return;
        }

        if (!$this->canProcessTask($task, $message->isRetry())) {
            return;
        }

        try {
            $this->processTask($task);
        } catch (\Throwable $e) {
            $this->handleTaskError($task, $e, $message->isRetry());
        }
    }

    private function findTask(string $taskId): ?AsyncImportTask
    {
        $task = $this->taskRepository->find($taskId);

        if (null === $task) {
            $this->logger->error('Import task not found', ['taskId' => $taskId]);
        }

        return $task;
    }

    private function processTask(AsyncImportTask $task): void
    {
        $this->importService->updateTaskStatus($task, ImportTaskStatus::PROCESSING);

        $parser = $this->getFileParser($task);
        $fileName = $task->getFile();
        if (null === $fileName) {
            throw new ImportTaskException('Task file name is null');
        }
        $filePath = $this->importService->getFilePath($fileName);

        $this->validateFileFormat($parser, $filePath);

        $entityClass = $task->getEntityClass();
        if (null === $entityClass) {
            throw new ImportTaskException('Task entity class is null');
        }
        $handler = $this->handlerRegistry->getHandler($entityClass);
        $this->progressTracker->startTracking($task);

        $this->dispatchBatchesForProcessing($task, $parser, $filePath, $handler);
    }

    private function getFileParser(AsyncImportTask $task): FileParserInterface
    {
        $fileType = $task->getFileType();
        if (null === $fileType) {
            $fileName = $task->getFile();
            if (null === $fileName) {
                throw new ImportTaskException('Task file name is null');
            }
            $fileType = $this->parserFactory->guessFileType($fileName);
            $task->setFileType($fileType);
        }

        return $this->parserFactory->getParser($fileType);
    }

    private function validateFileFormat(FileParserInterface $parser, string $filePath): void
    {
        $validationResult = $parser->validateFormat($filePath);
        if (!$validationResult->isValid()) {
            throw new ImportTaskException('文件格式验证失败: ' . $validationResult->getErrorMessage());
        }
    }

    private function dispatchBatchesForProcessing(AsyncImportTask $task, FileParserInterface $parser, string $filePath, ImportHandlerInterface $handler): void
    {
        $options = $task->getImportConfig() ?? [];
        $batchSize = $handler->getBatchSize();
        /** @var array<array<string, mixed>> $rows */
        $rows = [];
        $lineNumber = 0;
        $batchStartLine = 1;

        foreach ($parser->parse($filePath, $options) as $row) {
            ++$lineNumber;
            if (!is_array($row)) {
                throw new ImportTaskException(sprintf('Row %d is not an array', $lineNumber));
            }

            // 确保所有行项都是标量或数组值
            /** @var array<string, mixed> $validatedRow */
            $validatedRow = [];
            foreach ($row as $key => $value) {
                if (!is_string($key)) {
                    throw new ImportTaskException(sprintf('Row %d has non-string key', $lineNumber));
                }
                $validatedRow[$key] = $value;
            }

            $rows[] = $validatedRow;

            if (count($rows) >= $batchSize) {
                $this->dispatchBatch($task, $rows, $batchStartLine, $lineNumber);
                $rows = [];
                $batchStartLine = $lineNumber + 1;
            }
        }

        // 处理最后一批数据
        if ([] !== $rows) {
            $this->dispatchBatch($task, $rows, $batchStartLine, $lineNumber);
        }

        $this->logTaskDispatchSuccess($task, $lineNumber, $batchSize);
    }

    /**
     * @param array<array<string, mixed>> $rows
     */
    private function dispatchBatch(AsyncImportTask $task, array $rows, int $batchStartLine, int $lineNumber): void
    {
        $taskId = $task->getId();
        if (null === $taskId) {
            throw new ImportTaskException('Task ID is null');
        }
        $batchMessage = new ProcessImportBatchMessage(
            $taskId,
            $rows,
            $batchStartLine,
            $lineNumber
        );
        $this->messageBus->dispatch($batchMessage);
    }

    private function logTaskDispatchSuccess(AsyncImportTask $task, int $lineNumber, int $batchSize): void
    {
        $this->logger->info('Import task dispatched for processing', [
            'taskId' => $task->getId(),
            'totalLines' => $lineNumber,
            'batches' => ceil($lineNumber / $batchSize),
        ]);
    }

    /**
     * 检查任务是否可以处理
     */
    private function canProcessTask(AsyncImportTask $task, bool $isRetry): bool
    {
        // 如果是重试，检查重试条件
        if ($isRetry) {
            if (!$this->importService->canTaskRetry($task)) {
                $this->logger->warning('Task cannot be retried', [
                    'taskId' => $task->getId(),
                    'retryCount' => $task->getRetryCount(),
                    'maxRetries' => $task->getMaxRetries(),
                ]);

                return false;
            }
            $this->importService->incrementTaskRetryCount($task);
            $this->entityManager->flush();
        }

        // 检查任务状态
        if (!in_array($task->getStatus(), [ImportTaskStatus::PENDING, ImportTaskStatus::FAILED], true)) {
            $this->logger->warning('Task is not in processable status', [
                'taskId' => $task->getId(),
                'status' => $task->getStatus()->value,
            ]);

            return false;
        }

        return true;
    }

    /**
     * 处理任务错误
     */
    private function handleTaskError(AsyncImportTask $task, \Throwable $e, bool $isRetry): void
    {
        $this->logger->error('Import task processing failed', [
            'taskId' => $task->getId(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->importService->failTask($task, (string) $e);

        // 如果可以重试，发送重试消息
        if (!$isRetry && $this->importService->canTaskRetry($task)) {
            $taskId = $task->getId();
            if (null === $taskId) {
                throw new ImportTaskException('Task ID is null');
            }
            $retryMessage = new ProcessImportTaskMessage($taskId, true);

            // 延迟重试（指数退避）
            $delay = pow(2, $task->getRetryCount()) * 60 * 1000; // 毫秒

            $this->messageBus->dispatch($retryMessage, [
                new DelayStamp($delay),
            ]);

            $this->logger->info('Task scheduled for retry', [
                'taskId' => $task->getId(),
                'retryCount' => $task->getRetryCount() + 1,
                'delay' => $delay,
            ]);
        }
    }
}
