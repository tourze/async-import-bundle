<?php

namespace AsyncImportBundle\Tests\Repository;

use AsyncImportBundle\Entity\AsyncImportErrorLog;
use AsyncImportBundle\Repository\AsyncImportErrorLogRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AsyncImportErrorLogRepository::class)]
#[RunTestsInSeparateProcesses]
final class AsyncImportErrorLogRepositoryTest extends AbstractRepositoryTestCase
{
    private AsyncImportErrorLogRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AsyncImportErrorLogRepository::class);
    }

    public function testSaveEntity(): void
    {
        $entity = new AsyncImportErrorLog();
        $entity->setEntityClass('App\Entity\User');
        $entity->setTaskId('task-123');
        $entity->setLine(1);
        $entity->setError('Test error');
        $entity->setRawRow(['test' => 'data']);
        $entity->setNewRow(['processed' => 'data']);

        $this->repository->save($entity, true);

        $this->assertEntityPersisted($entity);
        $this->assertSame('task-123', $entity->getTaskId());
        $this->assertSame(1, $entity->getLine());
        $this->assertSame('Test error', $entity->getError());
    }

    public function testRemoveEntity(): void
    {
        $entity = new AsyncImportErrorLog();
        $entity->setEntityClass('App\Entity\User');
        $entity->setTaskId('task-456');
        $entity->setLine(2);
        $entity->setError('Another error');
        $entity->setRawRow(['test' => 'data2']);
        $entity->setNewRow(['processed' => 'data2']);

        $this->repository->save($entity, true);
        $entityId = $entity->getId();
        $this->assertNotNull($entityId);

        $this->repository->remove($entity, true);

        $this->assertEntityNotExists(AsyncImportErrorLog::class, $entityId);
    }

    public function testFindByTaskIdWithMatchingCriteria(): void
    {
        $taskId = 'task-789';

        $entity1 = new AsyncImportErrorLog();
        $entity1->setEntityClass('App\Entity\User');
        $entity1->setTaskId($taskId);
        $entity1->setLine(1);
        $entity1->setError('Error 1');
        $entity1->setRawRow(['test' => 'data1']);
        $entity1->setNewRow(['processed' => 'data1']);

        $entity2 = new AsyncImportErrorLog();
        $entity2->setEntityClass('App\Entity\User');
        $entity2->setTaskId($taskId);
        $entity2->setLine(2);
        $entity2->setError('Error 2');
        $entity2->setRawRow(['test' => 'data2']);
        $entity2->setNewRow(['processed' => 'data2']);

        $entity3 = new AsyncImportErrorLog();
        $entity3->setEntityClass('App\Entity\User');
        $entity3->setTaskId('other-task');
        $entity3->setLine(1);
        $entity3->setError('Other error');
        $entity3->setRawRow(['test' => 'data3']);
        $entity3->setNewRow(['processed' => 'data3']);

        $this->persistEntities([$entity1, $entity2, $entity3]);

        $results = $this->repository->findByTaskId($taskId);

        $this->assertCount(2, $results);
        $this->assertSame($entity1->getId(), $results[0]->getId());
        $this->assertSame($entity2->getId(), $results[1]->getId());
    }

    public function testFindByTaskIdWithNonMatchingCriteria(): void
    {
        $results = $this->repository->findByTaskId('non-existent-task');

        $this->assertEmpty($results);
    }

    public function testFindByTaskIdRespectLimitAndOffset(): void
    {
        $taskId = 'task-limit-test';

        for ($i = 1; $i <= 5; ++$i) {
            $entity = new AsyncImportErrorLog();
            $entity->setEntityClass('App\Entity\User');
            $entity->setTaskId($taskId);
            $entity->setLine($i);
            $entity->setError("Error {$i}");
            $entity->setRawRow(['test' => "data{$i}"]);
            $entity->setNewRow(['processed' => "data{$i}"]);
            $this->repository->save($entity, false);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findByTaskId($taskId, 2, 1);

        $this->assertCount(2, $results);
        $this->assertSame(2, $results[0]->getLine());
        $this->assertSame(3, $results[1]->getLine());
    }

    public function testCountByTaskIdWithMatchingCriteria(): void
    {
        $taskId = 'task-count-test';

        $entity1 = new AsyncImportErrorLog();
        $entity1->setEntityClass('App\Entity\User');
        $entity1->setTaskId($taskId);
        $entity1->setLine(1);
        $entity1->setError('Error 1');
        $entity1->setRawRow(['test' => 'data1']);
        $entity1->setNewRow(['processed' => 'data1']);

        $entity2 = new AsyncImportErrorLog();
        $entity2->setEntityClass('App\Entity\User');
        $entity2->setTaskId($taskId);
        $entity2->setLine(2);
        $entity2->setError('Error 2');
        $entity2->setRawRow(['test' => 'data2']);
        $entity2->setNewRow(['processed' => 'data2']);

        $this->persistEntities([$entity1, $entity2]);

        $count = $this->repository->countByTaskId($taskId);

        $this->assertSame(2, $count);
    }

    public function testCountByTaskIdWithNonMatchingCriteria(): void
    {
        $count = $this->repository->countByTaskId('non-existent-task');

        $this->assertSame(0, $count);
    }

    public function testDeleteByTaskId(): void
    {
        $taskId = 'task-delete-test';

        $entity1 = new AsyncImportErrorLog();
        $entity1->setEntityClass('App\Entity\User');
        $entity1->setTaskId($taskId);
        $entity1->setLine(1);
        $entity1->setError('Error 1');
        $entity1->setRawRow(['test' => 'data1']);
        $entity1->setNewRow(['processed' => 'data1']);

        $entity2 = new AsyncImportErrorLog();
        $entity2->setEntityClass('App\Entity\User');
        $entity2->setTaskId($taskId);
        $entity2->setLine(2);
        $entity2->setError('Error 2');
        $entity2->setRawRow(['test' => 'data2']);
        $entity2->setNewRow(['processed' => 'data2']);

        $entity3 = new AsyncImportErrorLog();
        $entity3->setEntityClass('App\Entity\User');
        $entity3->setTaskId('other-task');
        $entity3->setLine(1);
        $entity3->setError('Other error');
        $entity3->setRawRow(['test' => 'data3']);
        $entity3->setNewRow(['processed' => 'data3']);

        $this->persistEntities([$entity1, $entity2, $entity3]);

        $deletedCount = $this->repository->deleteByTaskId($taskId);

        $this->assertSame(2, $deletedCount);
        $this->assertSame(0, $this->repository->countByTaskId($taskId));
        $this->assertSame(1, $this->repository->countByTaskId('other-task'));
    }

    public function testFindRecent(): void
    {
        $baseTime = new \DateTimeImmutable('2024-01-01 12:00:00');

        for ($i = 1; $i <= 3; ++$i) {
            $entity = new AsyncImportErrorLog();
            $entity->setEntityClass('App\Entity\User');
            $entity->setTaskId("task-{$i}");
            $entity->setLine($i);
            $entity->setError("Error {$i}");
            $entity->setRawRow(['test' => "data{$i}"]);
            $entity->setNewRow(['processed' => "data{$i}"]);

            // 显式设定递增的时间戳，确保顺序可控
            $entity->setCreateTime($baseTime->modify("+{$i} seconds"));

            $this->repository->save($entity, false);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findRecent(2);

        $this->assertCount(2, $results);
        // Should be ordered by createTime DESC, so latest first
        // Check that we got recent results and they are properly ordered
        $this->assertGreaterThanOrEqual(2, count($results));
        $firstCreateTime = $results[0]->getCreateTime();
        $secondCreateTime = $results[1]->getCreateTime();
        $this->assertGreaterThanOrEqual($secondCreateTime, $firstCreateTime);
    }

    public function testFindOneByWithSortingLogic(): void
    {
        $uniqueTaskId = 'sorting-test-' . uniqid();

        $entity1 = new AsyncImportErrorLog();
        $entity1->setEntityClass('App\Entity\User');
        $entity1->setTaskId($uniqueTaskId);
        $entity1->setLine(2);
        $entity1->setError('Error B');
        $entity1->setRawRow(['test' => 'data1']);
        $entity1->setNewRow(['processed' => 'data1']);

        $entity2 = new AsyncImportErrorLog();
        $entity2->setEntityClass('App\Entity\User');
        $entity2->setTaskId($uniqueTaskId);
        $entity2->setLine(1);
        $entity2->setError('Error A');
        $entity2->setRawRow(['test' => 'data2']);
        $entity2->setNewRow(['processed' => 'data2']);

        $this->persistEntities([$entity1, $entity2]);

        $result = $this->repository->findOneBy(['taskId' => $uniqueTaskId], ['line' => 'ASC']);

        $this->assertNotNull($result);
        $this->assertSame(1, $result->getLine());
        $this->assertSame('Error A', $result->getError());
    }

    public function testFindByWithNullableFieldIsNull(): void
    {
        $uniquePrefix = 'null-test-' . uniqid();

        $entityWithError = new AsyncImportErrorLog();
        $entityWithError->setEntityClass('App\Entity\User');
        $entityWithError->setTaskId($uniquePrefix . '-with-error');
        $entityWithError->setLine(1);
        $entityWithError->setError('Not null error');
        $entityWithError->setRawRow(['test' => 'data1']);
        $entityWithError->setNewRow(['processed' => 'data1']);

        // Create entity with null error (though error is required, testing null handling)
        $entityWithoutError = new AsyncImportErrorLog();
        $entityWithoutError->setEntityClass('App\Entity\User');
        $entityWithoutError->setTaskId($uniquePrefix . '-without-error');
        $entityWithoutError->setLine(2);
        $entityWithoutError->setError(''); // Empty string to simulate null-like behavior
        $entityWithoutError->setRawRow(['test' => 'data2']);
        $entityWithoutError->setNewRow(['processed' => 'data2']);

        $this->persistEntities([$entityWithError, $entityWithoutError]);

        $results = $this->repository->findBy(['error' => '']);

        // Filter our test results
        $ourResults = array_filter($results, function ($entity) use ($uniquePrefix) {
            return 0 === strpos($entity->getTaskId() ?? '', $uniquePrefix);
        });
        $ourResults = array_values($ourResults);

        $this->assertCount(1, $ourResults);
        $this->assertSame($entityWithoutError->getId(), $ourResults[0]->getId());
    }

    public function testCountWithNullableFieldIsNull(): void
    {
        $uniquePrefix = 'count-null-test-' . uniqid();

        $entityWithError = new AsyncImportErrorLog();
        $entityWithError->setEntityClass('App\Entity\User');
        $entityWithError->setTaskId($uniquePrefix . '-with-error');
        $entityWithError->setLine(1);
        $entityWithError->setError('Not null error');
        $entityWithError->setRawRow(['test' => 'data1']);
        $entityWithError->setNewRow(['processed' => 'data1']);

        $entityWithEmptyError = new AsyncImportErrorLog();
        $entityWithEmptyError->setEntityClass('App\Entity\User');
        $entityWithEmptyError->setTaskId($uniquePrefix . '-empty-error');
        $entityWithEmptyError->setLine(2);
        $entityWithEmptyError->setError(''); // Empty string to simulate null-like behavior
        $entityWithEmptyError->setRawRow(['test' => 'data2']);
        $entityWithEmptyError->setNewRow(['processed' => 'data2']);

        $this->persistEntities([$entityWithError, $entityWithEmptyError]);

        $count = $this->repository->count(['error' => '']);

        $this->assertGreaterThanOrEqual(1, $count);
    }

    protected function createNewEntity(): object
    {
        // 由于 AsyncImportErrorLog 是只读实体，我们直接返回一个实体实例
        // 基础测试不需要持久化操作
        $entity = new AsyncImportErrorLog();
        $entity->setEntityClass('App\Entity\User');
        $entity->setTaskId('test-task-' . uniqid());
        $entity->setLine(1);
        $entity->setError('Test error');
        $entity->setRawRow(['test' => 'data']);
        $entity->setNewRow(['processed' => 'data']);

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<AsyncImportErrorLog>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
