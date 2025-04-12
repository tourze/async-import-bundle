<?php

namespace AsyncImportBundle\Entity;

use AsyncImportBundle\Repository\AsyncImportErrorLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;

/**
 * 这里记录的是第几行的错误
 */
#[ORM\Entity(repositoryClass: AsyncImportErrorLogRepository::class, readOnly: true)]
#[ORM\Table(name: 'async_import_error_log', options: ['comment' => '异步导入错误日志'])]
class AsyncImportErrorLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = 0;

    #[ORM\Column(type: Types::STRING, length: 1000, options: ['comment' => '实体类名'])]
    private ?string $entityClass = null;

    #[IndexColumn]
    #[ORM\Column(length: 40, options: ['comment' => '任务ID'])]
    private ?string $taskId = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '第几行'])]
    private int $line = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '异常信息'])]
    private ?string $error = null;

    #[ORM\Column(nullable: true, options: ['comment' => '队列原始数据'])]
    private ?array $rawRow = null;

    #[ORM\Column(nullable: true, options: ['comment' => '格式化数据'])]
    private ?array $newRow = null;

    #[IndexColumn]
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(?string $entityClass): void
    {
        $this->entityClass = $entityClass;
    }

    public function getTaskId(): ?string
    {
        return $this->taskId;
    }

    public function setTaskId(?string $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function setLine(int $line): void
    {
        $this->line = $line;
    }

    public function getRawRow(): ?array
    {
        return $this->rawRow;
    }

    public function setRawRow(?array $rawRow): static
    {
        $this->rawRow = $rawRow;

        return $this;
    }

    public function getNewRow(): ?array
    {
        return $this->newRow;
    }

    public function setNewRow(?array $newRow): static
    {
        $this->newRow = $newRow;

        return $this;
    }

    public function setCreateTime(?\DateTimeInterface $createdAt): self
    {
        $this->createTime = $createdAt;

        return $this;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }
}
