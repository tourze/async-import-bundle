<?php

namespace AsyncImportBundle\Tests\MessageHandler;

use AsyncImportBundle\Message\CleanupImportTaskMessage;
use AsyncImportBundle\MessageHandler\CleanupImportTaskHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(CleanupImportTaskHandler::class)]
#[RunTestsInSeparateProcesses]
final class CleanupImportTaskHandlerTest extends AbstractIntegrationTestCase
{
    private CleanupImportTaskHandler $handler;

    protected function onSetUp(): void
    {
        // 从容器获取服务
        $this->handler = self::getService(CleanupImportTaskHandler::class);
    }

    public function testHandlerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(CleanupImportTaskHandler::class, $this->handler);
    }

    public function testInvokeWithMessage(): void
    {
        $message = new CleanupImportTaskMessage(daysToKeep: 30);

        $this->expectNotToPerformAssertions();
        $this->handler->__invoke($message);
    }
}
