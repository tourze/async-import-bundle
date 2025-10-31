<?php

namespace AsyncImportBundle\Tests\Service;

use AsyncImportBundle\Entity\AsyncImportErrorLog;
use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Repository\AsyncImportErrorLogRepository;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use AsyncImportBundle\Service\AsyncImportService;
use AsyncImportBundle\Service\FileParserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 */
#[CoversClass(AsyncImportService::class)]
final class AsyncImportServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private AsyncImportTaskRepository $taskRepository;

    private AsyncImportErrorLogRepository $errorLogRepository;

    private FileParserFactory $parserFactory;

    private Security $security;

    private LoggerInterface $logger;

    private AsyncImportService $service;

    protected function setUp(): void
    {
        parent::setUp();

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
            '/tmp'
        );
    }

    public function testCanTaskRetry(): void
    {
        $task = new AsyncImportTask();
        $task->setStatus(ImportTaskStatus::FAILED);
        $task->setRetryCount(2);
        $task->setMaxRetries(3);

        $this->assertTrue($this->service->canTaskRetry($task));

        $task->setRetryCount(3);
        $this->assertFalse($this->service->canTaskRetry($task));

        // Test with non-failed status
        $task->setStatus(ImportTaskStatus::PENDING);
        $task->setRetryCount(1);
        $this->assertFalse($this->service->canTaskRetry($task));
    }

    public function testCreateTask(): void
    {
        /** @var UserInterface&MockObject $user */
        $user = $this->createMock(UserInterface::class);

        /** @var Security&MockObject $security */
        $security = $this->security;
        $security->method('getUser')->willReturn($user);

        /** @var UploadedFile&MockObject $file */
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('test.csv');
        $file->method('getClientOriginalExtension')->willReturn('csv');
        $file->method('move')->willReturn($file);
        $file->method('getPathname')->willReturn('/tmp/test.csv');

        $entityClass = 'App\Entity\User';
        $options = ['handler' => 'UserHandler'];

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->entityManager;
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $task = $this->service->createTask($file, $entityClass, $options);

        $this->assertInstanceOf(AsyncImportTask::class, $task);
        $this->assertSame($entityClass, $task->getEntityClass());
        $this->assertSame(ImportTaskStatus::PENDING, $task->getStatus());
    }

    public function testIncrementTaskRetryCount(): void
    {
        $task = new AsyncImportTask();
        $task->setRetryCount(1);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->entityManager;
        $entityManager->expects($this->once())->method('flush');

        $this->service->incrementTaskRetryCount($task);

        $this->assertSame(2, $task->getRetryCount());
    }

    public function testUpdateTaskStatus(): void
    {
        $task = new AsyncImportTask();
        $initialStatus = ImportTaskStatus::PENDING;
        $newStatus = ImportTaskStatus::PROCESSING;

        $task->setStatus($initialStatus);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->entityManager;
        $entityManager->expects($this->once())->method('flush');

        $this->service->updateTaskStatus($task, $newStatus);

        $this->assertSame($newStatus, $task->getStatus());
        $this->assertNotNull($task->getStartTime());
    }

    public function testLogError(): void
    {
        $task = new AsyncImportTask();
        $task->setId('1');
        $task->setEntityClass('TestEntity');
        $task->setFailCount(0);

        $lineNumber = 5;
        $error = 'Test error message';
        $rawData = ['column1' => 'value1'];
        $processedData = ['field1' => 'processed_value1'];

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->entityManager;
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $this->service->logError($task, $lineNumber, $error, $rawData, $processedData);

        $this->assertSame($error, $task->getLastErrorMessage());
        $this->assertNotNull($task->getLastErrorTime());
        $this->assertSame(1, $task->getFailCount());
    }

    public function testCleanupOldTasks(): void
    {
        $daysToKeep = 30;

        // Mock old tasks to be cleaned up
        /** @var AsyncImportTask&MockObject $task1 */
        $task1 = $this->createMock(AsyncImportTask::class);
        $task1->method('getId')->willReturn('1');
        $task1->method('getFile')->willReturn('old-file1.csv');

        /** @var AsyncImportTask&MockObject $task2 */
        $task2 = $this->createMock(AsyncImportTask::class);
        $task2->method('getId')->willReturn('2');
        $task2->method('getFile')->willReturn('old-file2.csv');

        $oldTasks = [$task1, $task2];

        /** @var AsyncImportTaskRepository&MockObject $taskRepository */
        $taskRepository = $this->taskRepository;
        $taskRepository->expects($this->once())
            ->method('findOldTasks')
            ->willReturn($oldTasks)
        ;

        // Mock entity manager remove calls
        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->entityManager;
        $entityManager->expects($this->exactly(2))->method('remove');
        $entityManager->expects($this->once())->method('flush');

        $result = $this->service->cleanupOldTasks($daysToKeep);

        $this->assertSame(2, $result);
    }

    public function testCompleteTask(): void
    {
        $task = new AsyncImportTask();
        $task->setStatus(ImportTaskStatus::PROCESSING);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->entityManager;
        $entityManager->expects($this->once())->method('flush');

        $this->service->completeTask($task);

        $this->assertSame(ImportTaskStatus::COMPLETED, $task->getStatus());
        $this->assertNotNull($task->getEndTime());
    }

    public function testFailTask(): void
    {
        $task = new AsyncImportTask();
        $task->setStatus(ImportTaskStatus::PROCESSING);
        $error = 'Test failure message';

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->entityManager;
        $entityManager->expects($this->once())->method('flush');

        $this->service->failTask($task, $error);

        $this->assertSame(ImportTaskStatus::FAILED, $task->getStatus());
        $this->assertSame($error, $task->getLastErrorMessage());
        $this->assertNotNull($task->getLastErrorTime());
        $this->assertNotNull($task->getEndTime());
    }

    public function testUpdateProgress(): void
    {
        $task = new AsyncImportTask();
        $task->setProcessCount(0);
        $task->setSuccessCount(10);
        $task->setFailCount(2);

        $processed = 15;
        $success = 5;
        $failed = 3;

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->entityManager;
        $entityManager->expects($this->once())->method('flush');

        $this->service->updateProgress($task, $processed, $success, $failed);

        $this->assertSame($processed, $task->getProcessCount());
        $this->assertSame(15, $task->getSuccessCount()); // 10 + 5
        $this->assertSame(5, $task->getFailCount()); // 2 + 3
    }

    public function testGetTaskProgressPercentage(): void
    {
        $task = new AsyncImportTask();
        $task->setProcessCount(50);
        $task->setTotalCount(200);

        $percentage = $this->service->getTaskProgressPercentage($task);

        $this->assertSame(25.0, $percentage);
    }

    public function testGetTaskProgressPercentageWithZeroTotal(): void
    {
        $task = new AsyncImportTask();
        $task->setProcessCount(0);
        $task->setTotalCount(0);

        $percentage = $this->service->getTaskProgressPercentage($task);

        $this->assertSame(0.0, $percentage);
    }
}
