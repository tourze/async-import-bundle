<?php

namespace AsyncImportBundle\Tests\Service;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Service\ImportProgressTracker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ImportProgressTracker::class)]
#[RunTestsInSeparateProcesses]
final class ImportProgressTrackerTest extends AbstractIntegrationTestCase
{
    private ImportProgressTracker $tracker;

    protected function onSetUp(): void
    {
        $this->tracker = self::getService(ImportProgressTracker::class);
    }

    /**
     * 创建一个用于测试的 AsyncImportTask 实例
     */
    private function createTestTask(): AsyncImportTask
    {
        $task = new AsyncImportTask();
        $task->setFile('test.csv');
        $task->setEntityClass('TestEntity');
        $task->setTotalCount(100);

        // 持久化任务以获取真实 ID
        $this->persistAndFlush($task);

        return $task;
    }

    public function testStartTracking(): void
    {
        $task = $this->createTestTask();

        $this->tracker->startTracking($task);

        $progress = $this->tracker->getProgress($task);
        $this->assertSame(0, $progress['processed']);
        $this->assertSame(0, $progress['success']);
        $this->assertSame(0, $progress['failed']);
    }

    public function testUpdateProgress(): void
    {
        $task = $this->createTestTask();

        // Note: Event dispatching is not tested in integration tests
        // as it requires complex event system mocking. Event functionality
        // should be tested via functional tests or using event listener mocks.
        $this->tracker->updateProgress($task, 50, 45, 5, false);

        $progress = $this->tracker->getProgress($task);
        $this->assertSame(50, $progress['processed']);
        $this->assertSame(45, $progress['success']);
        $this->assertSame(5, $progress['failed']);
    }

    public function testUpdateProgressWithDispatchEvent(): void
    {
        $task = $this->createTestTask();

        // Test that updateProgress with dispatchEvent=true does not throw
        $this->tracker->updateProgress($task, 30, 28, 2, true);

        $progress = $this->tracker->getProgress($task);
        $this->assertSame(30, $progress['processed']);
        $this->assertSame(28, $progress['success']);
        $this->assertSame(2, $progress['failed']);
    }

    public function testStopTracking(): void
    {
        $task = $this->createTestTask();
        $task->setProcessCount(100);
        $task->setSuccessCount(95);
        $task->setFailCount(5);
        $task->setTotalCount(100);

        $this->tracker->startTracking($task);
        $this->tracker->updateProgress($task, 100, 95, 5, false);

        // 添加小延迟以确保duration不为0
        usleep(1000);

        $stats = $this->tracker->stopTracking($task);

        $this->assertArrayHasKey('duration', $stats);
        $this->assertArrayHasKey('averageSpeed', $stats);
        $this->assertGreaterThan(0, $stats['duration']);
        $this->assertSame(100, $stats['processed']);

        // 停止后，getProgress应该返回task自身的数据
        $progress = $this->tracker->getProgress($task);
        $this->assertArrayHasKey('processed', $progress);
    }

    public function testGetProgressWithoutTracking(): void
    {
        $task = $this->createTestTask();
        $task->setProcessCount(20);
        $task->setSuccessCount(18);
        $task->setFailCount(2);
        $task->setTotalCount(100);

        $progress = $this->tracker->getProgress($task);

        $this->assertSame(20, $progress['processed']);
        $this->assertSame(18, $progress['success']);
        $this->assertSame(2, $progress['failed']);
        $this->assertSame(100, $progress['total']);
        $this->assertSame(20.0, $progress['percentage']);
        $this->assertSame(0, $progress['speed']);
        $this->assertNull($progress['eta']);
    }
}
