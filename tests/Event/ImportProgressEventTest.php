<?php

namespace AsyncImportBundle\Tests\Event;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Event\ImportProgressEvent;
use PHPUnit\Framework\TestCase;

class ImportProgressEventTest extends TestCase
{
    public function testGetters(): void
    {
        $task = $this->createMock(AsyncImportTask::class);
        $task->method('getProgressPercentage')->willReturn(75.5);
        
        $event = new ImportProgressEvent(
            task: $task,
            processed: 100,
            success: 75,
            failed: 25,
            speed: 10.5,
            eta: 120
        );
        
        $this->assertSame($task, $event->getTask());
        $this->assertSame(100, $event->getProcessed());
        $this->assertSame(75, $event->getSuccess());
        $this->assertSame(25, $event->getFailed());
        $this->assertSame(10.5, $event->getSpeed());
        $this->assertSame(120, $event->getEta());
        $this->assertSame(75.5, $event->getPercentage());
    }
    
    public function testWithNullEta(): void
    {
        $task = $this->createMock(AsyncImportTask::class);
        
        $event = new ImportProgressEvent(
            task: $task,
            processed: 50,
            success: 50,
            failed: 0,
            speed: 5.0,
            eta: null
        );
        
        $this->assertNull($event->getEta());
    }
}