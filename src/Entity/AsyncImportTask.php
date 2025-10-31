<?php

namespace AsyncImportBundle\Entity;

use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 异步：导入任务
 */
#[ORM\Entity(repositoryClass: AsyncImportTaskRepository::class)]
#[ORM\Table(name: 'curd_import_task', options: ['comment' => '异步：导入任务'])]
class AsyncImportTask implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户ID'])]
    private ?string $userId = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    #[ORM\Column(type: Types::STRING, length: 1000, options: ['comment' => '文件名'])]
    private ?string $file = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    #[ORM\Column(type: Types::STRING, length: 1000, options: ['comment' => '实体类名'])]
    private ?string $entityClass = null;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    #[Assert\NotNull]
    #[Assert\Choice(callback: [ImportTaskStatus::class, 'cases'])]
    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 20, enumType: ImportTaskStatus::class, options: ['comment' => '任务状态'])]
    private ImportTaskStatus $status = ImportTaskStatus::PENDING;

    #[Assert\Choice(callback: [ImportFileType::class, 'cases'])]
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, enumType: ImportFileType::class, options: ['comment' => '文件类型'])]
    private ?ImportFileType $fileType = null;

    /** @var array<string, mixed>|null */
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '导入配置'])]
    private ?array $importConfig = null;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '成功数'])]
    private int $successCount = 0;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '失败数'])]
    private int $failCount = 0;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '重试次数'])]
    private int $retryCount = 0;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 3, 'comment' => '最大重试次数'])]
    private int $maxRetries = 3;

    #[Assert\Range(min: -100, max: 100)]
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '优先级'])]
    private int $priority = 0;

    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '开始时间'])]
    private ?\DateTimeImmutable $startTime = null;

    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '结束时间'])]
    private ?\DateTimeImmutable $endTime = null;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '最后错误信息'])]
    private ?string $lastErrorMessage = null;

    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后错误时间'])]
    private ?\DateTimeImmutable $lastErrorTime = null;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0, 'comment' => '总行数'])]
    private ?int $totalCount = 0;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0, 'comment' => '已处理'])]
    private ?int $processCount = 0;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0, 'comment' => '内存占用'])]
    private ?int $memoryUsage = 0;

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(string $file): void
    {
        $this->file = $file;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): void
    {
        $this->entityClass = $entityClass;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function getLastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }

    public function setLastErrorMessage(?string $lastErrorMessage): void
    {
        $this->lastErrorMessage = $lastErrorMessage;
    }

    public function getLastErrorTime(): ?\DateTimeImmutable
    {
        return $this->lastErrorTime;
    }

    public function setLastErrorTime(?\DateTimeImmutable $lastErrorTime): void
    {
        $this->lastErrorTime = $lastErrorTime;
    }

    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

    public function setTotalCount(?int $totalCount): void
    {
        $this->totalCount = $totalCount;
    }

    public function getProcessCount(): ?int
    {
        return $this->processCount;
    }

    public function setProcessCount(?int $processCount): void
    {
        $this->processCount = $processCount;
    }

    public function getMemoryUsage(): ?int
    {
        return $this->memoryUsage;
    }

    public function setMemoryUsage(?int $memoryUsage): void
    {
        $this->memoryUsage = $memoryUsage;
    }

    public function getStatus(): ImportTaskStatus
    {
        return $this->status;
    }

    public function setStatus(ImportTaskStatus $status): void
    {
        $this->status = $status;
    }

    public function getFileType(): ?ImportFileType
    {
        return $this->fileType;
    }

    public function setFileType(ImportFileType $fileType): void
    {
        $this->fileType = $fileType;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getImportConfig(): ?array
    {
        return $this->importConfig;
    }

    /**
     * @param array<string, mixed>|null $importConfig
     */
    public function setImportConfig(?array $importConfig): void
    {
        $this->importConfig = $importConfig;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function setSuccessCount(int $successCount): void
    {
        $this->successCount = $successCount;
    }

    public function getFailCount(): int
    {
        return $this->failCount;
    }

    public function setFailCount(int $failCount): void
    {
        $this->failCount = $failCount;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function setRetryCount(int $retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function setMaxRetries(int $maxRetries): void
    {
        $this->maxRetries = $maxRetries;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeImmutable $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeImmutable $endTime): void
    {
        $this->endTime = $endTime;
    }

    public function __toString(): string
    {
        return sprintf(
            '导入任务#%s - %s (%s)',
            $this->id ?? 'new',
            basename($this->file ?? '未知文件'),
            $this->status->getLabel()
        );
    }
}
