<?php

namespace AsyncImportBundle\Message;

/**
 * 清理过期导入任务消息
 */
readonly class CleanupImportTaskMessage
{
    public function __construct(
        private int $daysToKeep = 30,
    ) {
    }

    public function getDaysToKeep(): int
    {
        return $this->daysToKeep;
    }
}
