<?php

namespace AsyncImportBundle\Repository;

use AsyncImportBundle\Entity\AsyncImportTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

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
}
