<?php

namespace AsyncImportBundle\Repository;

use AsyncImportBundle\Entity\AsyncImportErrorLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<AsyncImportErrorLog>
 */
#[AsRepository(entityClass: AsyncImportErrorLog::class)]
class AsyncImportErrorLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AsyncImportErrorLog::class);
    }

    /**
     * 按任务ID查找错误日志
     *
     * @return AsyncImportErrorLog[]
     */
    public function findByTaskId(string $taskId, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.taskId = :taskId')
            ->setParameter('taskId', $taskId)
            ->orderBy('e.line', 'ASC')
            ->addOrderBy('e.createTime', 'ASC')
        ;

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }

        /** @var array<AsyncImportErrorLog> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 统计任务的错误数量
     */
    public function countByTaskId(string $taskId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.taskId = :taskId')
            ->setParameter('taskId', $taskId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 删除任务的所有错误日志
     */
    public function deleteByTaskId(string $taskId): int
    {
        /** @var int */
        return $this->createQueryBuilder('e')
            ->delete()
            ->where('e.taskId = :taskId')
            ->setParameter('taskId', $taskId)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * 按错误类型分组统计
     *
     * @return array<array{error: string, count: int}>
     */
    public function getErrorStatistics(string $taskId): array
    {
        /** @var array<array{error: string, count: string}> */
        $result = $this->createQueryBuilder('e')
            ->select('e.error, COUNT(e.id) as count')
            ->where('e.taskId = :taskId')
            ->setParameter('taskId', $taskId)
            ->groupBy('e.error')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $statistics[] = [
                'error' => (string) $row['error'],
                'count' => (int) $row['count'],
            ];
        }

        return $statistics;
    }

    /**
     * 获取最近的错误日志
     *
     * @return AsyncImportErrorLog[]
     */
    public function findRecent(int $limit = 100): array
    {
        /** @var array<AsyncImportErrorLog> */
        return $this->createQueryBuilder('e')
            ->orderBy('e.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 保存实体
     */
    public function save(AsyncImportErrorLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 移除实体
     */
    public function remove(AsyncImportErrorLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
