<?php

namespace AsyncImportBundle\MessageHandler;

use AsyncImportBundle\Message\CleanupImportTaskMessage;
use AsyncImportBundle\Service\AsyncImportService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * 清理过期导入任务的消息处理器
 */
#[AsMessageHandler]
class CleanupImportTaskHandler
{
    public function __construct(
        private readonly AsyncImportService $importService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(CleanupImportTaskMessage $message): void
    {
        try {
            $this->logger->info('Starting cleanup of old import tasks', [
                'daysToKeep' => $message->getDaysToKeep()
            ]);
            
            $deletedCount = $this->importService->cleanupOldTasks($message->getDaysToKeep());
            
            $this->logger->info('Cleanup completed', [
                'deletedCount' => $deletedCount,
                'daysToKeep' => $message->getDaysToKeep()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to cleanup old import tasks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}