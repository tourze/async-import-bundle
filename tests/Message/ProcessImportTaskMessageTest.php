<?php

namespace AsyncImportBundle\Tests\Message;

use AsyncImportBundle\Message\ProcessImportTaskMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ProcessImportTaskMessage::class)]
final class ProcessImportTaskMessageTest extends TestCase
{
    public function testDefaultIsRetryFalse(): void
    {
        $message = new ProcessImportTaskMessage(taskId: '12345');

        $this->assertSame('12345', $message->getTaskId());
        $this->assertFalse($message->isRetry());
    }

    public function testWithRetryTrue(): void
    {
        $message = new ProcessImportTaskMessage(
            taskId: '67890',
            isRetry: true
        );

        $this->assertSame('67890', $message->getTaskId());
        $this->assertTrue($message->isRetry());
    }
}
