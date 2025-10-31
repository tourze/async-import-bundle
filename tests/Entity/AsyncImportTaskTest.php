<?php

namespace AsyncImportBundle\Tests\Entity;

use AsyncImportBundle\Entity\AsyncImportTask;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AsyncImportTask::class)]
final class AsyncImportTaskTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new AsyncImportTask();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'successCount' => ['successCount', 123],
            'failCount' => ['failCount', 123],
            'retryCount' => ['retryCount', 123],
            'maxRetries' => ['maxRetries', 123],
            'priority' => ['priority', 123],
        ];
    }

    private AsyncImportTask $task;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUp();
        $this->task = new AsyncImportTask();
    }

    public function testIdDefaultValue(): void
    {
        // 测试初始值为null
        $this->assertNull($this->task->getId());
    }

    public function testUserSetterAndGetter(): void
    {
        // 测试直接设置用户ID
        $this->task->setUserId('test-user-id');
        $this->assertSame('test-user-id', $this->task->getUserId());

        // 测试设置另一个用户ID
        $this->task->setUserId('another-user-id');
        $this->assertSame('another-user-id', $this->task->getUserId());

        // 测试设置null
        $this->task->setUserId(null);
        $this->assertNull($this->task->getUserId());
    }

    public function testFileSetterAndGetter(): void
    {
        $fileName = 'test-file.xlsx';

        // 测试设置值
        $this->task->setFile($fileName);
        $this->assertSame($fileName, $this->task->getFile());
    }

    public function testEntityClassSetterAndGetter(): void
    {
        $className = 'App\Entity\TestEntity';

        // 测试设置值
        $this->task->setEntityClass($className);
        $this->assertSame($className, $this->task->getEntityClass());
    }

    public function testRemarkSetterAndGetter(): void
    {
        $remark = 'Test remark content';

        // 测试设置值
        $this->task->setRemark($remark);
        $this->assertSame($remark, $this->task->getRemark());

        // 测试设置null
        $this->task->setRemark(null);
        $this->assertNull($this->task->getRemark());
    }

    public function testLastErrorMessageSetterAndGetter(): void
    {
        $errorMsg = 'Test error message';

        // 测试设置值
        $this->task->setLastErrorMessage($errorMsg);
        $this->assertSame($errorMsg, $this->task->getLastErrorMessage());

        // 测试设置null
        $this->task->setLastErrorMessage(null);
        $this->assertNull($this->task->getLastErrorMessage());
    }

    public function testLastErrorTimeSetterAndGetter(): void
    {
        $now = new \DateTimeImmutable();

        // 测试设置值
        $this->task->setLastErrorTime($now);
        $this->assertSame($now, $this->task->getLastErrorTime());

        // 测试设置null
        $this->task->setLastErrorTime(null);
        $this->assertNull($this->task->getLastErrorTime());
    }

    public function testTotalCountSetterAndGetter(): void
    {
        $count = 1000;

        // 测试设置值
        $this->task->setTotalCount($count);
        $this->assertSame($count, $this->task->getTotalCount());

        // 测试设置零值
        $this->task->setTotalCount(0);
        $this->assertSame(0, $this->task->getTotalCount());

        // 测试设置负值
        $this->task->setTotalCount(-10);
        $this->assertSame(-10, $this->task->getTotalCount());

        // 测试设置null
        $this->task->setTotalCount(null);
        $this->assertNull($this->task->getTotalCount());
    }

    public function testProcessCountSetterAndGetter(): void
    {
        $count = 500;

        // 测试设置值
        $this->task->setProcessCount($count);
        $this->assertSame($count, $this->task->getProcessCount());

        // 测试设置零值
        $this->task->setProcessCount(0);
        $this->assertSame(0, $this->task->getProcessCount());

        // 测试设置负值
        $this->task->setProcessCount(-5);
        $this->assertSame(-5, $this->task->getProcessCount());

        // 测试设置null
        $this->task->setProcessCount(null);
        $this->assertNull($this->task->getProcessCount());
    }

    public function testMemoryUsageSetterAndGetter(): void
    {
        $memory = 1048576; // 1MB

        // 测试设置值
        $this->task->setMemoryUsage($memory);
        $this->assertSame($memory, $this->task->getMemoryUsage());

        // 测试设置零值
        $this->task->setMemoryUsage(0);
        $this->assertSame(0, $this->task->getMemoryUsage());

        // 测试设置null
        $this->task->setMemoryUsage(null);
        $this->assertNull($this->task->getMemoryUsage());
    }

    public function testCreateTimeSetterAndGetter(): void
    {
        $now = new \DateTimeImmutable();

        // 测试设置值
        $this->task->setCreateTime($now);
        $this->assertSame($now, $this->task->getCreateTime());

        // 测试设置null
        $this->task->setCreateTime(null);
        $this->assertNull($this->task->getCreateTime());
    }

    public function testUpdateTimeSetterAndGetter(): void
    {
        $now = new \DateTimeImmutable();

        // 测试设置值
        $this->task->setUpdateTime($now);
        $this->assertSame($now, $this->task->getUpdateTime());

        // 测试设置null
        $this->task->setUpdateTime(null);
        $this->assertNull($this->task->getUpdateTime());
    }

    public function testCreatedBySetterAndGetter(): void
    {
        $userId = 'user123';

        // 测试设置值
        $this->task->setCreatedBy($userId);
        $this->assertSame($userId, $this->task->getCreatedBy());

        // 测试设置null
        $this->task->setCreatedBy(null);
        $this->assertNull($this->task->getCreatedBy());
    }

    public function testUpdatedBySetterAndGetter(): void
    {
        $userId = 'user456';

        // 测试设置值
        $this->task->setUpdatedBy($userId);
        $this->assertSame($userId, $this->task->getUpdatedBy());

        // 测试设置null
        $this->task->setUpdatedBy(null);
        $this->assertNull($this->task->getUpdatedBy());
    }
}
