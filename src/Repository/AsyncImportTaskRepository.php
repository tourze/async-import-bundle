<?php

namespace AsyncImportBundle\Repository;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportTaskStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<AsyncImportTask>
 */
#[AsRepository(entityClass: AsyncImportTask::class)]
class AsyncImportTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AsyncImportTask::class);
    }

    /**
     * 查找待处理的任务
     *
     * @return AsyncImportTask[]
     */
    public function findPendingTasks(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', ImportTaskStatus::PENDING)
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.createTime', 'ASC')
        ;

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        /** @var array<AsyncImportTask> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找可重试的失败任务
     *
     * @return AsyncImportTask[]
     */
    public function findRetryableTasks(): array
    {
        /** @var array<AsyncImportTask> */
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.retryCount < t.maxRetries')
            ->setParameter('status', ImportTaskStatus::FAILED)
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.lastErrorTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找过期的任务
     *
     * @return AsyncImportTask[]
     */
    public function findOldTasks(\DateTimeInterface $beforeDate): array
    {
        /** @var array<AsyncImportTask> */
        return $this->createQueryBuilder('t')
            ->where('t.createTime < :date')
            ->setParameter('date', $beforeDate)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按用户查找任务
     *
     * @param string|UserInterface $user 用户ID或用户对象
     * @param array<string, string> $orderBy
     *
     * @return AsyncImportTask[]
     */
    public function findByUser($user, array $orderBy = ['createTime' => 'DESC'], ?int $limit = null, ?int $offset = null): array
    {
        $userId = is_string($user) ? $user : $user->getUserIdentifier();

        $qb = $this->createQueryBuilder('t')
            ->where('t.userId = :userId')
            ->setParameter('userId', $userId)
        ;

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy('t.' . $field, $direction);
        }

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }

        /** @var array<AsyncImportTask> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 统计任务状态
     *
     * @return array<string, int>
     */
    public function getStatusStatistics(): array
    {
        /** @var array<array{status: ImportTaskStatus, count: string}> */
        $result = $this->createQueryBuilder('t')
            ->select('t.status as status, COUNT(t.id) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['status']->value] = (int) $row['count'];
        }

        return $statistics;
    }

    /**
     * 保存实体
     */
    public function save(AsyncImportTask $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 移除实体
     */
    public function remove(AsyncImportTask $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
