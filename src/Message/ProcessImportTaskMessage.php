<?php

namespace AsyncImportBundle\Message;

/**
 * 处理导入任务消息
 */
class ProcessImportTaskMessage
{
    public function __construct(
        private readonly string $taskId,
        private readonly bool $isRetry = false
    ) {
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function isRetry(): bool
    {
        return $this->isRetry;
    }
}