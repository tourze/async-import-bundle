<?php

namespace AsyncImportBundle\Message;

/**
 * 处理导入批次消息
 */
class ProcessImportBatchMessage
{
    public function __construct(
        private readonly string $taskId,
        private readonly array $rows,
        private readonly int $startLine,
        private readonly int $endLine
    ) {
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function getStartLine(): int
    {
        return $this->startLine;
    }

    public function getEndLine(): int
    {
        return $this->endLine;
    }

    public function getRowCount(): int
    {
        return count($this->rows);
    }
}