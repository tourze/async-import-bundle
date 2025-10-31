<?php

namespace AsyncImportBundle\Tests\Message;

use AsyncImportBundle\Message\CleanupImportTaskMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CleanupImportTaskMessage::class)]
final class CleanupImportTaskMessageTest extends TestCase
{
    public function testDefaultDaysToKeep(): void
    {
        $message = new CleanupImportTaskMessage();

        $this->assertSame(30, $message->getDaysToKeep());
    }

    public function testCustomDaysToKeep(): void
    {
        $message = new CleanupImportTaskMessage(daysToKeep: 60);

        $this->assertSame(60, $message->getDaysToKeep());
    }
}
