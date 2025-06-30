<?php

namespace AsyncImportBundle\Tests\Service;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Event\ImportProgressEvent;
use AsyncImportBundle\Service\ImportProgressTracker;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ImportProgressTrackerTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;
    private ImportProgressTracker $tracker;
    
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->tracker = new ImportProgressTracker($this->eventDispatcher);
    }
    
    public function testStartTracking(): void
    {
        $task = $this->createMock(AsyncImportTask::class);
        $task->expects($this->any())
            ->method('getId')
            ->willReturn('12345');
        
        $this->tracker->startTracking($task);
        
        $progress = $this->tracker->getProgress($task);
        $this->assertSame(0, $progress['processed']);
        $this->assertSame(0, $progress['success']);
        $this->assertSame(0, $progress['failed']);
    }
    
    public function testUpdateProgress(): void
    {
        $task = $this->createMock(AsyncImportTask::class);
        $task->method('getId')->willReturn('12345');
        $task->method('getTotalCount')->willReturn(100);
        
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ImportProgressEvent::class));
        
        $this->tracker->updateProgress($task, 50, 45, 5);
        
        $progress = $this->tracker->getProgress($task);
        $this->assertSame(50, $progress['processed']);
        $this->assertSame(45, $progress['success']);
        $this->assertSame(5, $progress['failed']);
    }
    
    public function testUpdateProgressWithoutEvent(): void
    {
        $task = $this->createMock(AsyncImportTask::class);
        $task->method('getId')->willReturn('12345');
        $task->method('getTotalCount')->willReturn(100);
        
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');
        
        $this->tracker->updateProgress($task, 30, 28, 2, false);
    }
    
    public function testStopTracking(): void
    {
        $task = $this->createMock(AsyncImportTask::class);
        $task->expects($this->any())
            ->method('getId')
            ->willReturn('12345');
        $task->method('getProcessCount')->willReturn(100);
        $task->method('getSuccessCount')->willReturn(95);
        $task->method('getFailCount')->willReturn(5);
        $task->method('getTotalCount')->willReturn(100);
        $task->method('getProgressPercentage')->willReturn(100.0);
        
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
        $task = $this->createMock(AsyncImportTask::class);
        $task->method('getId')->willReturn('12345');
        $task->method('getProcessCount')->willReturn(20);
        $task->method('getSuccessCount')->willReturn(18);
        $task->method('getFailCount')->willReturn(2);
        $task->method('getTotalCount')->willReturn(100);
        $task->method('getProgressPercentage')->willReturn(20.0);
        
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