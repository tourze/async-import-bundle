<?php

namespace AsyncImportBundle\Entity;

use AsyncImportBundle\Repository\AsyncImportErrorLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;

/**
 * 这里记录的是第几行的错误
 */
#[ORM\Entity(repositoryClass: AsyncImportErrorLogRepository::class, readOnly: true)]
#[ORM\Table(name: 'async_import_error_log', options: ['comment' => '异步导入错误日志'])]
class AsyncImportErrorLog implements \Stringable
{
    use CreateTimeAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    #[ORM\Column(type: Types::STRING, length: 1000, options: ['comment' => '实体类名'])]
    private ?string $entityClass = null;

    #[Assert\Length(max: 40)]
    #[IndexColumn]
    #[ORM\Column(length: 40, options: ['comment' => '任务ID'])]
    private ?string $taskId = null;

    #[Assert\PositiveOrZero]
    #[IndexColumn]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '第几行'])]
    private int $line = 0;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '异常信息'])]
    private ?string $error = null;

    /** @var array<string, mixed>|null */
    #[Assert\Type(type: 'array')]
    #[ORM\Column(nullable: true, options: ['comment' => '队列原始数据'])]
    private ?array $rawRow = null;

    /** @var array<string, mixed>|null */
    #[Assert\Type(type: 'array')]
    #[ORM\Column(nullable: true, options: ['comment' => '格式化数据'])]
    private ?array $newRow = null;

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

    /**
     * @return array<string, mixed>|null
     */
    public function getRawRow(): ?array
    {
        return $this->rawRow;
    }

    /**
     * @param array<string, mixed>|null $rawRow
     */
    public function setRawRow(?array $rawRow): void
    {
        $this->rawRow = $rawRow;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getNewRow(): ?array
    {
        return $this->newRow;
    }

    /**
     * @param array<string, mixed>|null $newRow
     */
    public function setNewRow(?array $newRow): void
    {
        $this->newRow = $newRow;
    }

    public function __toString(): string
    {
        return sprintf('AsyncImportErrorLog #%d', $this->getId() ?? 0);
    }
}
