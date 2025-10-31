<?php

namespace AsyncImportBundle\Tests\Service;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Event\ImportProgressEvent;
use AsyncImportBundle\Service\AsyncImportService;
use AsyncImportBundle\Service\ImportProgressTracker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[CoversClass(ImportProgressTracker::class)]
final class ImportProgressTrackerTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;

    private ImportProgressTracker $tracker;

    private AsyncImportService $asyncImportService;

    private function setUpImportProgressTracker(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->asyncImportService = $this->createMock(AsyncImportService::class);
        $this->tracker = new ImportProgressTracker($this->eventDispatcher, $this->asyncImportService);
    }

    public function testStartTracking(): void
    {
        $this->setUpImportProgressTracker();

        $task = $this->createMock(AsyncImportTask::class);
        $task->method('getId')->willReturn('12345');

        $this->tracker->startTracking($task);

        $progress = $this->tracker->getProgress($task);
        $this->assertSame(0, $progress['processed']);
        $this->assertSame(0, $progress['success']);
        $this->assertSame(0, $progress['failed']);
    }

    public function testUpdateProgress(): void
    {
        $this->setUpImportProgressTracker();

        /** @var AsyncImportTask&MockObject $task */
        $task = $this->createMock(AsyncImportTask::class);
        $task->method('getId')->willReturn('12345');
        $task->method('getTotalCount')->willReturn(100);

        /** @var EventDispatcherInterface&MockObject $eventDispatcher */
        $eventDispatcher = $this->eventDispatcher;
        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::callback(static function (object $event): bool {
                return $event instanceof ImportProgressEvent;
            }))
        ;

        $this->tracker->updateProgress($task, 50, 45, 5);

        $progress = $this->tracker->getProgress($task);
        $this->assertSame(50, $progress['processed']);
        $this->assertSame(45, $progress['success']);
        $this->assertSame(5, $progress['failed']);
    }

    public function testUpdateProgressWithoutEvent(): void
    {
        $this->setUpImportProgressTracker();

        /** @var AsyncImportTask&MockObject $task */
        $task = $this->createMock(AsyncImportTask::class);
        $task->method('getId')->willReturn('12345');
        $task->method('getTotalCount')->willReturn(100);

        /** @var EventDispatcherInterface&MockObject $eventDispatcher */
        $eventDispatcher = $this->eventDispatcher;
        $eventDispatcher->expects($this->never())
            ->method('dispatch')
        ;

        $this->tracker->updateProgress($task, 30, 28, 2, false);
    }

    public function testStopTracking(): void
    {
        $this->setUpImportProgressTracker();

        $task = $this->createMock(AsyncImportTask::class);
        $task->method('getId')->willReturn('12345');
        $task->method('getProcessCount')->willReturn(100);
        $task->method('getSuccessCount')->willReturn(95);
        $task->method('getFailCount')->willReturn(5);
        $task->method('getTotalCount')->willReturn(100);

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
        $this->setUpImportProgressTracker();

        /** @var AsyncImportTask&MockObject $task */
        $task = $this->createMock(AsyncImportTask::class);
        $task->method('getId')->willReturn('12345');
        $task->method('getProcessCount')->willReturn(20);
        $task->method('getSuccessCount')->willReturn(18);
        $task->method('getFailCount')->willReturn(2);
        $task->method('getTotalCount')->willReturn(100);

        /** @var AsyncImportService&MockObject $asyncImportService */
        $asyncImportService = $this->asyncImportService;
        $asyncImportService->method('getTaskProgressPercentage')
            ->with($task)
            ->willReturn(20.0)
        ;

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
