<?php

namespace AsyncImportBundle\Tests\Event;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Event\ImportProgressEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(ImportProgressEvent::class)]
final class ImportProgressEventTest extends AbstractEventTestCase
{
    public function testGetters(): void
    {
        // 使用匿名类替代Mock以满足静态分析要求
        $task = new class extends AsyncImportTask {
            // 仅用作事件数据载体，无需实现具体方法
        };

        $event = new ImportProgressEvent(
            task: $task,
            processed: 100,
            success: 75,
            failed: 25,
            speed: 10.5,
            eta: 120,
            percentage: 75.5
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
        // 使用匿名类替代Mock以满足静态分析要求
        $task = new class extends AsyncImportTask {
            // 仅用作事件数据载体，无需实现具体方法
        };

        $event = new ImportProgressEvent(
            task: $task,
            processed: 50,
            success: 50,
            failed: 0,
            speed: 5.0,
            eta: null,
            percentage: 50.0
        );

        $this->assertNull($event->getEta());
    }
}
