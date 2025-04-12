<?php

namespace AsyncImportBundle\Repository;

use AsyncImportBundle\Entity\AsyncImportErrorLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AsyncImportErrorLog>
 * @method AsyncImportErrorLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method AsyncImportErrorLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method AsyncImportErrorLog[] findAll()
 * @method AsyncImportErrorLog[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AsyncImportErrorLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AsyncImportErrorLog::class);
    }
}
