<?php

namespace AsyncImportBundle\Tests\MessageHandler;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Message\ProcessImportBatchMessage;
use AsyncImportBundle\MessageHandler\ProcessImportBatchHandler;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use AsyncImportBundle\Service\AsyncImportService;
use AsyncImportBundle\Service\ImportHandlerInterface;
use AsyncImportBundle\Service\ImportHandlerRegistry;
use AsyncImportBundle\Service\ImportProgressTracker;
use AsyncImportBundle\Service\ValidationResult;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProcessImportBatchHandlerTest extends TestCase
{
    private AsyncImportTaskRepository $taskRepository;
    private AsyncImportService $importService;
    private ImportHandlerRegistry $handlerRegistry;
    private ImportProgressTracker $progressTracker;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ProcessImportBatchHandler $handler;
    
    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(AsyncImportTaskRepository::class);
        $this->importService = $this->createMock(AsyncImportService::class);
        $this->handlerRegistry = $this->createMock(ImportHandlerRegistry::class);
        $this->progressTracker = $this->createMock(ImportProgressTracker::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->handler = new ProcessImportBatchHandler(
            $this->taskRepository,
            $this->importService,
            $this->handlerRegistry,
            $this->progressTracker,
            $this->entityManager,
            $this->logger
        );
    }
    
    public function testTaskNotFound(): void
    {
        $message = new ProcessImportBatchMessage('12345', [], 1, 10);
        
        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with('12345')
            ->willReturn(null);
        
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Import task not found', ['taskId' => '12345']);
        
        $this->handler->__invoke($message);
    }
    
    public function testTaskNotInProcessingStatus(): void
    {
        $task = $this->createMock(AsyncImportTask::class);
        $task->method('getId')->willReturn('12345');
        $task->method('getStatus')->willReturn(ImportTaskStatus::COMPLETED);
        
        $message = new ProcessImportBatchMessage('12345', [], 1, 10);
        
        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with('12345')
            ->willReturn($task);
        
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Task is not in processing status', [
                'taskId' => '12345',
                'status' => ImportTaskStatus::COMPLETED->value
            ]);
        
        $this->handler->__invoke($message);
    }
    
    public function testSuccessfulBatchProcessing(): void
    {
        $task = $this->createMock(AsyncImportTask::class);
        $task->method('getId')->willReturn('12345');
        $task->method('getStatus')->willReturn(ImportTaskStatus::PROCESSING);
        $task->method('getEntityClass')->willReturn('App\Entity\User');
        $task->method('getProcessCount')->willReturn(10);
        $task->method('getTotalCount')->willReturn(100);
        $task->method('getSuccessCount')->willReturn(8);
        $task->method('getFailCount')->willReturn(2);
        
        $rows = [
            ['id' => 1, 'name' => 'Test 1'],
            ['id' => 2, 'name' => 'Test 2'],
        ];
        
        $message = new ProcessImportBatchMessage('12345', $rows, 11, 12);
        
        $importHandler = $this->createMock(ImportHandlerInterface::class);
        
        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with('12345')
            ->willReturn($task);
        
        $this->handlerRegistry->expects($this->once())
            ->method('getHandler')
            ->with('App\Entity\User')
            ->willReturn($importHandler);
        
        $validationResult = new ValidationResult();
        
        $importHandler->expects($this->exactly(2))
            ->method('preprocess')
            ->willReturnArgument(0);
        
        $importHandler->expects($this->exactly(2))
            ->method('validate')
            ->willReturn($validationResult);
        
        $importHandler->expects($this->exactly(2))
            ->method('import');
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->importService->expects($this->once())
            ->method('updateProgress')
            ->with($task, 12, 2, 0);
        
        $this->progressTracker->expects($this->once())
            ->method('updateProgress');
        
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Batch processed successfully');
        
        $this->handler->__invoke($message);
    }
}