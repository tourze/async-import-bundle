<?php

namespace AsyncImportBundle\Tests\MessageHandler;

use AsyncImportBundle\Message\ProcessImportBatchMessage;
use AsyncImportBundle\MessageHandler\ProcessImportBatchHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ProcessImportBatchHandler::class)]
#[RunTestsInSeparateProcesses]
final class ProcessImportBatchHandlerTest extends AbstractIntegrationTestCase
{
    private ProcessImportBatchHandler $handler;

    protected function onSetUp(): void
    {
        $this->handler = self::getService(ProcessImportBatchHandler::class);
    }

    public function testHandlerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ProcessImportBatchHandler::class, $this->handler);
    }

    public function testInvokeWithMessage(): void
    {
        $message = new ProcessImportBatchMessage('task-id', [], 1, 1);

        $this->expectNotToPerformAssertions();
        $this->handler->__invoke($message);
    }
}
