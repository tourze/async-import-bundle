<?php

namespace AsyncImportBundle\Tests\Service;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Service\AsyncImportService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AsyncImportService::class)]
#[RunTestsInSeparateProcesses]
final class AsyncImportServiceTest extends AbstractIntegrationTestCase
{
    private AsyncImportService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(AsyncImportService::class);
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
        // Skip this test in integration environment as it requires
        // file system configuration (upload_dir) which is not available
        // in the test kernel. This functionality should be tested via
        // functional tests or by mocking the file system.
        // @phpstan-ignore staticMethod.dynamicCall
        $this->markTestSkipped(
            'File upload functionality requires upload_dir configuration '
            . 'which is not available in integration test environment. '
            . 'Test this functionality via functional tests instead.'
        );
    }

    public function testIncrementTaskRetryCount(): void
    {
        $task = new AsyncImportTask();
        $task->setEntityClass('TestEntity');
        $task->setFile('test.csv');
        $task->setStatus(ImportTaskStatus::PENDING);
        $task->setRetryCount(1);

        $this->persistAndFlush($task);

        $this->service->incrementTaskRetryCount($task);

        $this->assertSame(2, $task->getRetryCount());
    }

    public function testUpdateTaskStatus(): void
    {
        $task = new AsyncImportTask();
        $task->setEntityClass('TestEntity');
        $task->setFile('test.csv');
        $task->setStatus(ImportTaskStatus::PENDING);

        $this->persistAndFlush($task);

        $newStatus = ImportTaskStatus::PROCESSING;
        $this->service->updateTaskStatus($task, $newStatus);

        $this->assertSame($newStatus, $task->getStatus());
        $this->assertNotNull($task->getStartTime());
    }

    public function testLogError(): void
    {
        $task = new AsyncImportTask();
        $task->setEntityClass('TestEntity');
        $task->setFile('test.csv');
        $task->setStatus(ImportTaskStatus::PROCESSING);
        $task->setFailCount(0);

        $this->persistAndFlush($task);

        $lineNumber = 5;
        $error = 'Test error message';
        $rawData = ['column1' => 'value1'];
        $processedData = ['field1' => 'processed_value1'];

        $this->service->logError($task, $lineNumber, $error, $rawData, $processedData);

        $this->assertSame($error, $task->getLastErrorMessage());
        $this->assertNotNull($task->getLastErrorTime());
        $this->assertSame(1, $task->getFailCount());
    }

    public function testCleanupOldTasks(): void
    {
        // Test cleanup method executes without errors
        // Note: Cannot easily test with old tasks in integration test
        // because createTime is auto-set by TimestampableAware trait
        $daysToKeep = 30;
        $result = $this->service->cleanupOldTasks($daysToKeep);

        // Should return 0 or positive number without throwing exception
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testCompleteTask(): void
    {
        $task = new AsyncImportTask();
        $task->setEntityClass('TestEntity');
        $task->setFile('test.csv');
        $task->setStatus(ImportTaskStatus::PROCESSING);

        $this->persistAndFlush($task);

        $this->service->completeTask($task);

        $this->assertSame(ImportTaskStatus::COMPLETED, $task->getStatus());
        $this->assertNotNull($task->getEndTime());
    }

    public function testFailTask(): void
    {
        $task = new AsyncImportTask();
        $task->setEntityClass('TestEntity');
        $task->setFile('test.csv');
        $task->setStatus(ImportTaskStatus::PROCESSING);

        $this->persistAndFlush($task);

        $error = 'Test failure message';
        $this->service->failTask($task, $error);

        $this->assertSame(ImportTaskStatus::FAILED, $task->getStatus());
        $this->assertSame($error, $task->getLastErrorMessage());
        $this->assertNotNull($task->getLastErrorTime());
        $this->assertNotNull($task->getEndTime());
    }

    public function testUpdateProgress(): void
    {
        $task = new AsyncImportTask();
        $task->setEntityClass('TestEntity');
        $task->setFile('test.csv');
        $task->setStatus(ImportTaskStatus::PROCESSING);
        $task->setProcessCount(0);
        $task->setSuccessCount(10);
        $task->setFailCount(2);

        $this->persistAndFlush($task);

        $processed = 15;
        $success = 5;
        $failed = 3;

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
