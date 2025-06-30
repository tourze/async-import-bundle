<?php

namespace AsyncImportBundle\Tests\MessageHandler;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Message\ProcessImportBatchMessage;
use AsyncImportBundle\Message\ProcessImportTaskMessage;
use AsyncImportBundle\MessageHandler\ProcessImportTaskHandler;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use AsyncImportBundle\Service\AsyncImportService;
use AsyncImportBundle\Service\FileParserFactory;
use AsyncImportBundle\Service\FileParserInterface;
use AsyncImportBundle\Service\ImportHandlerRegistry;
use AsyncImportBundle\Service\ImportProgressTracker;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class ProcessImportTaskHandlerTest extends TestCase
{
    private AsyncImportTaskRepository $taskRepository;
    private AsyncImportService $importService;
    private FileParserFactory $parserFactory;
    private ImportHandlerRegistry $handlerRegistry;
    private ImportProgressTracker $progressTracker;
    private MessageBusInterface $messageBus;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ProcessImportTaskHandler $handler;
    
    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(AsyncImportTaskRepository::class);
        $this->importService = $this->createMock(AsyncImportService::class);
        $this->parserFactory = $this->createMock(FileParserFactory::class);
        $this->handlerRegistry = $this->createMock(ImportHandlerRegistry::class);
        $this->progressTracker = $this->createMock(ImportProgressTracker::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->handler = new ProcessImportTaskHandler(
            $this->taskRepository,
            $this->importService,
            $this->parserFactory,
            $this->handlerRegistry,
            $this->progressTracker,
            $this->messageBus,
            $this->entityManager,
            $this->logger
        );
    }
    
    public function testTaskNotFound(): void
    {
        $message = new ProcessImportTaskMessage('12345');
        
        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with('12345')
            ->willReturn(null);
        
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Import task not found', ['taskId' => '12345']);
        
        $this->handler->__invoke($message);
    }
    
    public function testProcessingNewTask(): void
    {
        $task = $this->createMock(AsyncImportTask::class);
        $task->method('getId')->willReturn('12345');
        $task->method('getStatus')->willReturn(ImportTaskStatus::PENDING);
        $task->method('getFile')->willReturn('/path/to/file.csv');
        $task->method('getFileType')->willReturn(ImportFileType::CSV);
        $task->method('getEntityClass')->willReturn('App\Entity\User');
        
        $message = new ProcessImportTaskMessage('12345');
        
        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with('12345')
            ->willReturn($task);
        
        $this->importService->expects($this->once())
            ->method('updateTaskStatus')
            ->with($task, ImportTaskStatus::PROCESSING);
        
        $parser = $this->createMock(FileParserInterface::class);
        $parser->expects($this->once())
            ->method('parse')
            ->willReturn(new \ArrayIterator([
                ['id' => 1, 'name' => 'Test 1'],
                ['id' => 2, 'name' => 'Test 2'],
            ]));
        
        $this->parserFactory->expects($this->once())
            ->method('getParser')
            ->with(ImportFileType::CSV)
            ->willReturn($parser);
        
        $this->importService->expects($this->once())
            ->method('getFilePath')
            ->with('/path/to/file.csv')
            ->willReturn('/absolute/path/to/file.csv');
        
        $validationResult = $this->createMock(\AsyncImportBundle\Service\ValidationResult::class);
        $validationResult->method('isValid')->willReturn(true);
        
        $parser->expects($this->once())
            ->method('validateFormat')
            ->willReturn($validationResult);
        
        $handler = $this->createMock(\AsyncImportBundle\Service\ImportHandlerInterface::class);
        $handler->method('getBatchSize')->willReturn(100);
        
        $this->handlerRegistry->expects($this->once())
            ->method('getHandler')
            ->with('App\Entity\User')
            ->willReturn($handler);
        
        $this->progressTracker->expects($this->once())
            ->method('startTracking')
            ->with($task);
        
        $this->messageBus->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(function ($message) {
                $this->assertInstanceOf(ProcessImportBatchMessage::class, $message);
                return new Envelope($message);
            });
        
        $this->logger->expects($this->atLeast(1))
            ->method('info');
        
        $this->handler->__invoke($message);
    }
}