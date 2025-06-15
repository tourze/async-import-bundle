<?php

namespace AsyncImportBundle\Repository;

use AsyncImportBundle\Entity\AsyncImportTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<AsyncImportTask>
 *
 * @method AsyncImportTask|null find($id, $lockMode = null, $lockVersion = null)
 * @method AsyncImportTask|null findOneBy(array $criteria, array $orderBy = null)
 * @method AsyncImportTask[]    findAll()
 * @method AsyncImportTask[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
#[Autoconfigure(lazy: true, public: true)]
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
            ->setParameter('status', \AsyncImportBundle\Enum\ImportTaskStatus::PENDING)
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.createTime', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 查找可重试的失败任务
     * 
     * @return AsyncImportTask[]
     */
    public function findRetryableTasks(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.retryCount < t.maxRetries')
            ->setParameter('status', \AsyncImportBundle\Enum\ImportTaskStatus::FAILED)
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.lastErrorTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找过期的任务
     * 
     * @return AsyncImportTask[]
     */
    public function findOldTasks(\DateTimeInterface $beforeDate): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.createTime < :date')
            ->setParameter('date', $beforeDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * 按用户查找任务
     * 
     * @param string|UserInterface $user 用户ID或用户对象
     * @return AsyncImportTask[]
     */
    public function findByUser($user, array $orderBy = ['createTime' => 'DESC'], ?int $limit = null, ?int $offset = null): array
    {
        $userId = is_string($user) ? $user : $user->getUserIdentifier();
        
        $qb = $this->createQueryBuilder('t')
            ->where('t.userId = :userId')
            ->setParameter('userId', $userId);

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy('t.' . $field, $direction);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 统计任务状态
     */
    public function getStatusStatistics(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.status as status, COUNT(t.id) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['status']->value] = (int) $row['count'];
        }

        return $statistics;
    }
}
