<?php

namespace AsyncImportBundle\Entity;

use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 异步：导入任务
 */
#[ORM\Entity(repositoryClass: AsyncImportTaskRepository::class)]
#[ORM\Table(name: 'curd_import_task', options: ['comment' => '异步：导入任务'])]
class AsyncImportTask implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户ID'])]
    private ?string $userId = null;

    #[ORM\Column(type: Types::STRING, length: 1000, options: ['comment' => '文件名'])]
    private ?string $file = null;

    #[ORM\Column(type: Types::STRING, length: 1000, options: ['comment' => '实体类名'])]
    private ?string $entityClass = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '任务状态'])]
    private ImportTaskStatus $status = ImportTaskStatus::PENDING;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, options: ['comment' => '文件类型'])]
    private ?ImportFileType $fileType = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '导入配置'])]
    private ?array $importConfig = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '成功数'])]
    private int $successCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '失败数'])]
    private int $failCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '重试次数'])]
    private int $retryCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 3, 'comment' => '最大重试次数'])]
    private int $maxRetries = 3;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '优先级'])]
    private int $priority = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '开始时间'])]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '结束时间'])]
    private ?\DateTimeImmutable $endTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '最后错误信息'])]
    private ?string $lastErrorMessage = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后错误时间'])]
    private ?\DateTimeImmutable $lastErrorTime = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0, 'comment' => '总行数'])]
    private ?int $totalCount = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0, 'comment' => '已处理'])]
    private ?int $processCount = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0, 'comment' => '内存占用'])]
    private ?int $memoryUsage = 0;


    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }


    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): self
    {
        $this->remark = $remark;

        return $this;
    }

    public function getLastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }

    public function setLastErrorMessage(?string $lastErrorMessage): static
    {
        $this->lastErrorMessage = $lastErrorMessage;

        return $this;
    }

    public function getLastErrorTime(): ?\DateTimeImmutable
    {
        return $this->lastErrorTime;
    }

    public function setLastErrorTime(?\DateTimeImmutable $lastErrorTime): static
    {
        $this->lastErrorTime = $lastErrorTime;

        return $this;
    }

    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

    public function setTotalCount(?int $totalCount): self
    {
        $this->totalCount = $totalCount;

        return $this;
    }

    public function getProcessCount(): ?int
    {
        return $this->processCount;
    }

    public function setProcessCount(?int $processCount): self
    {
        $this->processCount = $processCount;

        return $this;
    }

    public function getMemoryUsage(): ?int
    {
        return $this->memoryUsage;
    }

    public function setMemoryUsage(?int $memoryUsage): self
    {
        $this->memoryUsage = $memoryUsage;

        return $this;
    }


    public function getStatus(): ImportTaskStatus
    {
        return $this->status;
    }

    public function setStatus(ImportTaskStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getFileType(): ?ImportFileType
    {
        return $this->fileType;
    }

    public function setFileType(?ImportFileType $fileType): self
    {
        $this->fileType = $fileType;

        return $this;
    }

    public function getImportConfig(): ?array
    {
        return $this->importConfig;
    }

    public function setImportConfig(?array $importConfig): self
    {
        $this->importConfig = $importConfig;

        return $this;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function setSuccessCount(int $successCount): self
    {
        $this->successCount = $successCount;

        return $this;
    }

    public function getFailCount(): int
    {
        return $this->failCount;
    }

    public function setFailCount(int $failCount): self
    {
        $this->failCount = $failCount;

        return $this;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function setRetryCount(int $retryCount): self
    {
        $this->retryCount = $retryCount;

        return $this;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function setMaxRetries(int $maxRetries): self
    {
        $this->maxRetries = $maxRetries;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeImmutable $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeImmutable $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * 计算进度百分比
     */
    public function getProgressPercentage(): float
    {
        if ($this->totalCount === 0 || $this->totalCount === null) {
            return 0.0;
        }

        return round(($this->processCount / $this->totalCount) * 100, 2);
    }

    /**
     * 是否可以重试
     */
    public function canRetry(): bool
    {
        return $this->status === ImportTaskStatus::FAILED && $this->retryCount < $this->maxRetries;
    }

    /**
     * 增加重试次数
     */
    public function incrementRetryCount(): self
    {
        $this->retryCount++;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('导入任务#%s - %s (%s)', 
            $this->id ?? 'new',
            basename($this->file ?? '未知文件'),
            $this->status->getLabel()
        );
    }
}
