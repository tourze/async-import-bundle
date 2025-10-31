<?php

namespace AsyncImportBundle\Tests\Repository;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Repository\AsyncImportTaskRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AsyncImportTaskRepository::class)]
#[RunTestsInSeparateProcesses]
final class AsyncImportTaskRepositoryTest extends AbstractRepositoryTestCase
{
    private AsyncImportTaskRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AsyncImportTaskRepository::class);
    }

    public function testSaveEntity(): void
    {
        $entity = new AsyncImportTask();
        $entity->setUserId('user-123');
        $entity->setFile('/tmp/test.csv');
        $entity->setEntityClass('App\Entity\User');
        $entity->setStatus(ImportTaskStatus::PENDING);

        $this->repository->save($entity, true);

        $this->assertEntityPersisted($entity);
        $this->assertSame('user-123', $entity->getUserId());
        $this->assertSame('/tmp/test.csv', $entity->getFile());
        $this->assertSame('App\Entity\User', $entity->getEntityClass());
    }

    public function testRemoveEntity(): void
    {
        $entity = new AsyncImportTask();
        $entity->setUserId('user-456');
        $entity->setFile('/tmp/test2.csv');
        $entity->setEntityClass('App\Entity\Product');
        $entity->setStatus(ImportTaskStatus::PENDING);

        $this->repository->save($entity, true);
        $entityId = $entity->getId();
        $this->assertNotNull($entityId);

        $this->repository->remove($entity, true);

        $this->assertEntityNotExists(AsyncImportTask::class, $entityId);
    }

    public function testFindPendingTasksWithMatchingStatus(): void
    {
        $pendingTask1 = new AsyncImportTask();
        $pendingTask1->setUserId('user-1');
        $pendingTask1->setFile('/tmp/pending1.csv');
        $pendingTask1->setEntityClass('App\Entity\User');
        $pendingTask1->setStatus(ImportTaskStatus::PENDING);
        $pendingTask1->setPriority(1);

        $pendingTask2 = new AsyncImportTask();
        $pendingTask2->setUserId('user-2');
        $pendingTask2->setFile('/tmp/pending2.csv');
        $pendingTask2->setEntityClass('App\Entity\Product');
        $pendingTask2->setStatus(ImportTaskStatus::PENDING);
        $pendingTask2->setPriority(2);

        $processingTask = new AsyncImportTask();
        $processingTask->setUserId('user-3');
        $processingTask->setFile('/tmp/processing.csv');
        $processingTask->setEntityClass('App\Entity\Order');
        $processingTask->setStatus(ImportTaskStatus::PROCESSING);

        $this->persistEntities([$pendingTask1, $pendingTask2, $processingTask]);

        $results = $this->repository->findPendingTasks();

        // Filter only our test entities by checking the user IDs we created
        $ourResults = array_filter($results, function ($task) {
            return in_array($task->getUserId(), ['user-1', 'user-2'], true);
        });
        $this->assertCount(2, $ourResults);
        // Should be ordered by priority DESC, then createTime ASC
        $ourResultsArray = array_values($ourResults);
        $this->assertTrue(
            ($ourResultsArray[0]->getId() === $pendingTask2->getId() && $ourResultsArray[1]->getId() === $pendingTask1->getId())
            || ($ourResultsArray[0]->getId() === $pendingTask1->getId() && $ourResultsArray[1]->getId() === $pendingTask2->getId())
        );
    }

    public function testFindPendingTasksWithLimit(): void
    {
        $uniquePrefix = 'limit-test-' . uniqid();
        for ($i = 1; $i <= 5; ++$i) {
            $task = new AsyncImportTask();
            $task->setUserId("{$uniquePrefix}-user-{$i}");
            $task->setFile("/tmp/pending{$i}.csv");
            $task->setEntityClass('App\Entity\User');
            $task->setStatus(ImportTaskStatus::PENDING);
            $task->setPriority($i);
            $this->repository->save($task, false);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findPendingTasks(20); // Use a larger limit to get all our test data

        // Filter our test results
        $ourResults = array_filter($results, function ($task) use ($uniquePrefix) {
            return 0 === strpos($task->getUserId() ?? '', $uniquePrefix);
        });
        $ourResults = array_values($ourResults); // Reset array keys

        $this->assertCount(5, $ourResults);
        // Should be ordered by priority DESC
        $this->assertSame(5, $ourResults[0]->getPriority());
        $this->assertSame(4, $ourResults[1]->getPriority());
        $this->assertSame(3, $ourResults[2]->getPriority());
    }

    public function testFindRetryableTasksWithFailedStatus(): void
    {
        $uniquePrefix = 'retry-test-' . uniqid();
        $retryableTask = new AsyncImportTask();
        $retryableTask->setUserId($uniquePrefix . '-user-1');
        $retryableTask->setFile('/tmp/retryable.csv');
        $retryableTask->setEntityClass('App\Entity\User');
        $retryableTask->setStatus(ImportTaskStatus::FAILED);
        $retryableTask->setRetryCount(1);
        $retryableTask->setMaxRetries(3);

        $nonRetryableTask = new AsyncImportTask();
        $nonRetryableTask->setUserId($uniquePrefix . '-user-2');
        $nonRetryableTask->setFile('/tmp/nonretryable.csv');
        $nonRetryableTask->setEntityClass('App\Entity\Product');
        $nonRetryableTask->setStatus(ImportTaskStatus::FAILED);
        $nonRetryableTask->setRetryCount(3);
        $nonRetryableTask->setMaxRetries(3);

        $completedTask = new AsyncImportTask();
        $completedTask->setUserId($uniquePrefix . '-user-3');
        $completedTask->setFile('/tmp/completed.csv');
        $completedTask->setEntityClass('App\Entity\Order');
        $completedTask->setStatus(ImportTaskStatus::COMPLETED);

        $this->persistEntities([$retryableTask, $nonRetryableTask, $completedTask]);

        $results = $this->repository->findRetryableTasks();

        // Filter our test results
        $ourResults = array_filter($results, function ($task) use ($uniquePrefix) {
            return 0 === strpos($task->getUserId() ?? '', $uniquePrefix);
        });
        $this->assertCount(1, $ourResults);
        $ourResultsArray = array_values($ourResults);
        $this->assertSame($retryableTask->getId(), $ourResultsArray[0]->getId());
    }

    public function testFindOldTasksBeforeDate(): void
    {
        $oldDate = new \DateTimeImmutable('-10 days');
        $recentDate = new \DateTimeImmutable('-1 day');

        $oldTask = new AsyncImportTask();
        $oldTask->setUserId('user-1');
        $oldTask->setFile('/tmp/old.csv');
        $oldTask->setEntityClass('App\Entity\User');
        $oldTask->setStatus(ImportTaskStatus::COMPLETED);
        $oldTask->setCreateTime($oldDate);

        $recentTask = new AsyncImportTask();
        $recentTask->setUserId('user-2');
        $recentTask->setFile('/tmp/recent.csv');
        $recentTask->setEntityClass('App\Entity\Product');
        $recentTask->setStatus(ImportTaskStatus::COMPLETED);
        $recentTask->setCreateTime($recentDate);

        $this->persistEntities([$oldTask, $recentTask]);

        $cutoffDate = new \DateTimeImmutable('-5 days');
        $results = $this->repository->findOldTasks($cutoffDate);

        $this->assertCount(1, $results);
        $this->assertSame($oldTask->getId(), $results[0]->getId());
    }

    public function testFindByUserWithStringUserId(): void
    {
        $uniquePrefix = 'find-by-user-test-' . uniqid();
        $userId = $uniquePrefix . '-test-user';

        $userTask1 = new AsyncImportTask();
        $userTask1->setUserId($userId);
        $userTask1->setFile('/tmp/user1.csv');
        $userTask1->setEntityClass('App\Entity\User');
        $userTask1->setStatus(ImportTaskStatus::PENDING);
        $userTask1->setCreateTime(new \DateTimeImmutable('-2 minutes'));

        $userTask2 = new AsyncImportTask();
        $userTask2->setUserId($userId);
        $userTask2->setFile('/tmp/user2.csv');
        $userTask2->setEntityClass('App\Entity\Product');
        $userTask2->setStatus(ImportTaskStatus::COMPLETED);
        $userTask2->setCreateTime(new \DateTimeImmutable('-1 minute'));

        $otherUserTask = new AsyncImportTask();
        $otherUserTask->setUserId($uniquePrefix . '-other-user');
        $otherUserTask->setFile('/tmp/other.csv');
        $otherUserTask->setEntityClass('App\Entity\Order');
        $otherUserTask->setStatus(ImportTaskStatus::PENDING);

        $this->persistEntities([$userTask1, $userTask2, $otherUserTask]);

        $results = $this->repository->findByUser($userId);

        $this->assertCount(2, $results);
        // Should be ordered by createTime DESC by default
        $this->assertSame($userTask2->getId(), $results[0]->getId());
        $this->assertSame($userTask1->getId(), $results[1]->getId());
    }

    public function testFindByUserWithCustomOrderBy(): void
    {
        $uniquePrefix = 'order-by-test-' . uniqid();
        $userId = $uniquePrefix . '-user';

        for ($i = 1; $i <= 3; ++$i) {
            $task = new AsyncImportTask();
            $task->setUserId($userId);
            $task->setFile("/tmp/order{$i}.csv");
            $task->setEntityClass('App\Entity\User');
            $task->setStatus(ImportTaskStatus::PENDING);
            $task->setPriority($i);
            $this->repository->save($task, false);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findByUser($userId, ['priority' => 'ASC']);

        $this->assertCount(3, $results);
        $this->assertSame(1, $results[0]->getPriority());
        $this->assertSame(2, $results[1]->getPriority());
        $this->assertSame(3, $results[2]->getPriority());
    }

    public function testFindByUserWithLimitAndOffset(): void
    {
        $uniquePrefix = 'pagination-test-' . uniqid();
        $userId = $uniquePrefix . '-user';

        for ($i = 1; $i <= 5; ++$i) {
            $task = new AsyncImportTask();
            $task->setUserId($userId);
            $task->setFile("/tmp/page{$i}.csv");
            $task->setEntityClass('App\Entity\User');
            $task->setStatus(ImportTaskStatus::PENDING);
            $task->setPriority($i);
            $this->repository->save($task, false);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findByUser($userId, ['priority' => 'ASC'], 2, 2);

        $this->assertCount(2, $results);
        $this->assertSame(3, $results[0]->getPriority());
        $this->assertSame(4, $results[1]->getPriority());
    }

    public function testGetStatusStatistics(): void
    {
        $uniquePrefix = 'statistics-test-' . uniqid();

        // Get baseline statistics before adding our test data
        $baselineStats = $this->repository->getStatusStatistics();
        $baselinePending = $baselineStats[ImportTaskStatus::PENDING->value] ?? 0;
        $baselineCompleted = $baselineStats[ImportTaskStatus::COMPLETED->value] ?? 0;
        $baselineFailed = $baselineStats[ImportTaskStatus::FAILED->value] ?? 0;

        $pendingTask = new AsyncImportTask();
        $pendingTask->setUserId($uniquePrefix . '-user-1');
        $pendingTask->setFile('/tmp/pending.csv');
        $pendingTask->setEntityClass('App\Entity\User');
        $pendingTask->setStatus(ImportTaskStatus::PENDING);

        $completedTask1 = new AsyncImportTask();
        $completedTask1->setUserId($uniquePrefix . '-user-2');
        $completedTask1->setFile('/tmp/completed1.csv');
        $completedTask1->setEntityClass('App\Entity\Product');
        $completedTask1->setStatus(ImportTaskStatus::COMPLETED);

        $completedTask2 = new AsyncImportTask();
        $completedTask2->setUserId($uniquePrefix . '-user-3');
        $completedTask2->setFile('/tmp/completed2.csv');
        $completedTask2->setEntityClass('App\Entity\Order');
        $completedTask2->setStatus(ImportTaskStatus::COMPLETED);

        $failedTask = new AsyncImportTask();
        $failedTask->setUserId($uniquePrefix . '-user-4');
        $failedTask->setFile('/tmp/failed.csv');
        $failedTask->setEntityClass('App\Entity\User');
        $failedTask->setStatus(ImportTaskStatus::FAILED);

        $this->persistEntities([$pendingTask, $completedTask1, $completedTask2, $failedTask]);

        $statistics = $this->repository->getStatusStatistics();

        $this->assertIsArray($statistics);
        $this->assertSame($baselinePending + 1, $statistics[ImportTaskStatus::PENDING->value]);
        $this->assertSame($baselineCompleted + 2, $statistics[ImportTaskStatus::COMPLETED->value]);
        $this->assertSame($baselineFailed + 1, $statistics[ImportTaskStatus::FAILED->value]);
    }

    public function testQueryWithNullableFieldIsNull(): void
    {
        $uniquePrefix = 'null-remark-test-' . uniqid();

        $taskWithRemark = new AsyncImportTask();
        $taskWithRemark->setUserId($uniquePrefix . '-user-with-remark');
        $taskWithRemark->setFile('/tmp/with-remark.csv');
        $taskWithRemark->setEntityClass('App\Entity\User');
        $taskWithRemark->setStatus(ImportTaskStatus::PENDING);
        $taskWithRemark->setRemark('This has a remark');

        $taskWithoutRemark = new AsyncImportTask();
        $taskWithoutRemark->setUserId($uniquePrefix . '-user-without-remark');
        $taskWithoutRemark->setFile('/tmp/without-remark.csv');
        $taskWithoutRemark->setEntityClass('App\Entity\Product');
        $taskWithoutRemark->setStatus(ImportTaskStatus::PENDING);
        // remark is null by default

        $this->persistEntities([$taskWithRemark, $taskWithoutRemark]);

        $results = $this->repository->findBy(['remark' => null]);

        // Filter our test results
        $ourResults = array_filter($results, function ($task) use ($uniquePrefix) {
            return 0 === strpos($task->getUserId() ?? '', $uniquePrefix);
        });
        $ourResults = array_values($ourResults);

        $this->assertCount(1, $ourResults);
        $this->assertSame($taskWithoutRemark->getId(), $ourResults[0]->getId());
    }

    public function testCountQueryWithNullableFieldIsNull(): void
    {
        $uniquePrefix = 'null-end-time-test-' . uniqid();

        // Get baseline count
        $baselineCount = $this->repository->count(['endTime' => null]);

        $taskWithEndTime = new AsyncImportTask();
        $taskWithEndTime->setUserId($uniquePrefix . '-user-with-end-time');
        $taskWithEndTime->setFile('/tmp/with-end-time.csv');
        $taskWithEndTime->setEntityClass('App\Entity\User');
        $taskWithEndTime->setStatus(ImportTaskStatus::COMPLETED);
        $taskWithEndTime->setEndTime(new \DateTimeImmutable());

        $taskWithoutEndTime = new AsyncImportTask();
        $taskWithoutEndTime->setUserId($uniquePrefix . '-user-without-end-time');
        $taskWithoutEndTime->setFile('/tmp/without-end-time.csv');
        $taskWithoutEndTime->setEntityClass('App\Entity\Product');
        $taskWithoutEndTime->setStatus(ImportTaskStatus::PENDING);
        // endTime is null by default

        $this->persistEntities([$taskWithEndTime, $taskWithoutEndTime]);

        $count = $this->repository->count(['endTime' => null]);

        $this->assertSame($baselineCount + 1, $count);
    }

    public function testFindOneByWithSortingLogic(): void
    {
        $uniquePrefix = 'sorting-test-' . uniqid();
        $userId = $uniquePrefix . '-user';

        $task1 = new AsyncImportTask();
        $task1->setUserId($userId);
        $task1->setFile('/tmp/priority2.csv');
        $task1->setEntityClass('App\Entity\User');
        $task1->setStatus(ImportTaskStatus::PENDING);
        $task1->setPriority(2);

        $task2 = new AsyncImportTask();
        $task2->setUserId($userId);
        $task2->setFile('/tmp/priority1.csv');
        $task2->setEntityClass('App\Entity\Product');
        $task2->setStatus(ImportTaskStatus::PENDING);
        $task2->setPriority(1);

        $this->persistEntities([$task1, $task2]);

        $result = $this->repository->findOneBy(['userId' => $userId], ['priority' => 'ASC']);

        $this->assertNotNull($result);
        $this->assertSame(1, $result->getPriority());
        $this->assertSame('/tmp/priority1.csv', $result->getFile());
    }

    protected function createNewEntity(): object
    {
        $entity = new AsyncImportTask();
        $entity->setUserId('test-user-' . uniqid());
        $entity->setFile('/tmp/test.csv');
        $entity->setEntityClass('App\Entity\User');
        $entity->setStatus(ImportTaskStatus::PENDING);
        $entity->setPriority(1);

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<AsyncImportTask>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
