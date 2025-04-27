<?php

namespace AsyncImportBundle\Entity;

use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;

/**
 * 异步：导入任务
 */
#[ORM\Entity(repositoryClass: AsyncImportTaskRepository::class)]
#[ORM\Table(name: 'curd_import_task', options: ['comment' => '异步：导入任务'])]
class AsyncImportTask
{
    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = '0';

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?UserInterface $user = null;

    #[ORM\Column(type: Types::STRING, length: 1000, options: ['comment' => '文件名'])]
    private ?string $file = null;

    #[ORM\Column(type: Types::STRING, length: 1000, options: ['comment' => '实体类名'])]
    private ?string $entityClass = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastErrorMessage = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastErrorTime = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0, 'comment' => '总行数'])]
    private ?int $totalCount = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0, 'comment' => '已处理'])]
    private ?int $processCount = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0, 'comment' => '内存占用'])]
    private ?int $memoryUsage = 0;

    #[IndexColumn]
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    #[CreatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[UpdatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '更新人'])]
    private ?string $updatedBy = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

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

    public function getLastErrorTime(): ?\DateTimeInterface
    {
        return $this->lastErrorTime;
    }

    public function setLastErrorTime(?\DateTimeInterface $lastErrorTime): static
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

    public function setCreateTime(?\DateTimeInterface $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }
}
