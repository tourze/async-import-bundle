<?php

namespace AsyncImportBundle\Event;

use AsyncImportBundle\Entity\AsyncImportTask;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * 导入进度更新事件
 */
class ImportProgressEvent extends Event
{
    public function __construct(
        private readonly AsyncImportTask $task,
        private readonly int $processed,
        private readonly int $success,
        private readonly int $failed,
        private readonly float $speed,
        private readonly ?int $eta
    ) {
    }

    public function getTask(): AsyncImportTask
    {
        return $this->task;
    }

    public function getProcessed(): int
    {
        return $this->processed;
    }

    public function getSuccess(): int
    {
        return $this->success;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }

    public function getSpeed(): float
    {
        return $this->speed;
    }

    public function getEta(): ?int
    {
        return $this->eta;
    }

    public function getPercentage(): float
    {
        return $this->task->getProgressPercentage();
    }
}