<?php

namespace AsyncImportBundle\Tests\MessageHandler;

use AsyncImportBundle\Message\CleanupImportTaskMessage;
use AsyncImportBundle\MessageHandler\CleanupImportTaskHandler;
use AsyncImportBundle\Service\AsyncImportService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CleanupImportTaskHandlerTest extends TestCase
{
    private AsyncImportService $importService;
    private LoggerInterface $logger;
    private CleanupImportTaskHandler $handler;
    
    protected function setUp(): void
    {
        $this->importService = $this->createMock(AsyncImportService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new CleanupImportTaskHandler(
            $this->importService,
            $this->logger
        );
    }
    
    public function testSuccessfulCleanup(): void
    {
        $message = new CleanupImportTaskMessage(daysToKeep: 30);
        
        $this->importService->expects($this->once())
            ->method('cleanupOldTasks')
            ->with(30)
            ->willReturn(5);
        
        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) {
                if ($message === 'Starting cleanup of old import tasks') {
                    $this->assertSame(['daysToKeep' => 30], $context);
                } elseif ($message === 'Cleanup completed') {
                    $this->assertSame(['deletedCount' => 5, 'daysToKeep' => 30], $context);
                }
            });
        
        $this->handler->__invoke($message);
    }
    
    public function testCleanupFailure(): void
    {
        $message = new CleanupImportTaskMessage(daysToKeep: 60);
        $exception = new \Exception('Database error');
        
        $this->importService->expects($this->once())
            ->method('cleanupOldTasks')
            ->with(60)
            ->willThrowException($exception);
        
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Starting cleanup of old import tasks', ['daysToKeep' => 60]);
        
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to cleanup old import tasks', [
                'error' => 'Database error',
                'trace' => $exception->getTraceAsString()
            ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');
        
        $this->handler->__invoke($message);
    }
}