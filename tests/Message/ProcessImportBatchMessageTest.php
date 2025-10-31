<?php

namespace AsyncImportBundle\Tests\Message;

use AsyncImportBundle\Message\ProcessImportBatchMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ProcessImportBatchMessage::class)]
final class ProcessImportBatchMessageTest extends TestCase
{
    public function testGetters(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'Test 1'],
            ['id' => 2, 'name' => 'Test 2'],
            ['id' => 3, 'name' => 'Test 3'],
        ];

        $message = new ProcessImportBatchMessage(
            taskId: '12345',
            rows: $rows,
            startLine: 10,
            endLine: 12
        );

        $this->assertSame('12345', $message->getTaskId());
        $this->assertSame($rows, $message->getRows());
        $this->assertSame(10, $message->getStartLine());
        $this->assertSame(12, $message->getEndLine());
        $this->assertSame(3, $message->getRowCount());
    }

    public function testEmptyRows(): void
    {
        $message = new ProcessImportBatchMessage(
            taskId: '67890',
            rows: [],
            startLine: 1,
            endLine: 0
        );

        $this->assertSame([], $message->getRows());
        $this->assertSame(0, $message->getRowCount());
    }
}
