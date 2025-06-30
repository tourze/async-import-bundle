<?php

namespace AsyncImportBundle\Tests\Service;

use AsyncImportBundle\Entity\AsyncImportErrorLog;
use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Repository\AsyncImportErrorLogRepository;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use AsyncImportBundle\Service\AsyncImportService;
use AsyncImportBundle\Service\FileParserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;

class AsyncImportServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private AsyncImportTaskRepository $taskRepository;
    private AsyncImportErrorLogRepository $errorLogRepository;
    private FileParserFactory $parserFactory;
    private Security $security;
    private LoggerInterface $logger;
    private AsyncImportService $service;
    private string $uploadDirectory = '/tmp/test-uploads';
    
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->taskRepository = $this->createMock(AsyncImportTaskRepository::class);
        $this->errorLogRepository = $this->createMock(AsyncImportErrorLogRepository::class);
        $this->parserFactory = $this->createMock(FileParserFactory::class);
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->service = new AsyncImportService(
            $this->entityManager,
            $this->taskRepository,
            $this->errorLogRepository,
            $this->parserFactory,
            $this->security,
            $this->logger,
            $this->uploadDirectory
        );
    }
    
    public function testCreateTaskWithAuthentication(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('test.csv');
        $file->method('getClientOriginalExtension')->willReturn('csv');
        $file->method('move')->willReturn($file);
        
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('user123');
        
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        
        $this->parserFactory->expects($this->once())
            ->method('guessFileType')
            ->willReturn(ImportFileType::CSV);
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(AsyncImportTask::class));
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $task = $this->service->createTask($file, 'App\Entity\User', ['remark' => 'Test import']);
        
        $this->assertInstanceOf(AsyncImportTask::class, $task);
        $this->assertSame('user123', $task->getUserId());
        $this->assertSame('App\Entity\User', $task->getEntityClass());
        $this->assertSame(ImportTaskStatus::PENDING, $task->getStatus());
    }
    
    public function testUpdateTaskStatus(): void
    {
        $task = new AsyncImportTask();
        $task->setStatus(ImportTaskStatus::PENDING);
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->service->updateTaskStatus($task, ImportTaskStatus::PROCESSING);
        
        $this->assertSame(ImportTaskStatus::PROCESSING, $task->getStatus());
        $this->assertNotNull($task->getStartTime());
    }
    
    public function testLogError(): void
    {
        $task = new AsyncImportTask();
        $task->setSuccessCount(5);
        $task->setFailCount(3);
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(AsyncImportErrorLog::class));
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->service->logError(
            $task,
            10,
            'Invalid data format',
            ['raw' => 'data'],
            ['processed' => 'data']
        );
        
        $this->assertSame(4, $task->getFailCount());
        $this->assertSame('Invalid data format', $task->getLastErrorMessage());
        $this->assertNotNull($task->getLastErrorTime());
    }
}