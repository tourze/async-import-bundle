<?php

namespace AsyncImportBundle\Message;

/**
 * 处理导入批次消息
 */
readonly class ProcessImportBatchMessage
{
    /**
     * @param array<array<string, mixed>> $rows
     */
    public function __construct(
        private string $taskId,
        private array $rows,
        private int $startLine,
        private int $endLine,
    ) {
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    /**
     * @return array<array<string, mixed>>
     */
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
