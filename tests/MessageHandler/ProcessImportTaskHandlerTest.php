<?php

namespace AsyncImportBundle\Tests\MessageHandler;

use AsyncImportBundle\Message\ProcessImportTaskMessage;
use AsyncImportBundle\MessageHandler\ProcessImportTaskHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ProcessImportTaskHandler::class)]
#[RunTestsInSeparateProcesses]
final class ProcessImportTaskHandlerTest extends AbstractIntegrationTestCase
{
    private ProcessImportTaskHandler $handler;

    protected function onSetUp(): void
    {
        $this->handler = self::getService(ProcessImportTaskHandler::class);
    }

    public function testHandlerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ProcessImportTaskHandler::class, $this->handler);
    }

    public function testInvokeWithMessage(): void
    {
        $message = new ProcessImportTaskMessage('task-id');

        $this->expectNotToPerformAssertions();
        $this->handler->__invoke($message);
    }
}
