<?php

namespace AsyncImportBundle\Tests\Entity;

use AsyncImportBundle\Entity\AsyncImportErrorLog;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AsyncImportErrorLog::class)]
final class AsyncImportErrorLogTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new AsyncImportErrorLog();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'line' => ['line', 123],
        ];
    }

    private AsyncImportErrorLog $errorLog;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUp();
        $this->errorLog = new AsyncImportErrorLog();
    }

    public function testIdDefaultValue(): void
    {
        // 初始值应为 0 (根据源码定义)
        $this->assertSame(0, $this->errorLog->getId());
    }

    public function testEntityClassSetterAndGetter(): void
    {
        $className = 'App\Entity\TestEntity';

        // 测试设置值
        $this->errorLog->setEntityClass($className);
        $this->assertSame($className, $this->errorLog->getEntityClass());

        // 测试设置null
        $this->errorLog->setEntityClass(null);
        $this->assertNull($this->errorLog->getEntityClass());
    }

    public function testTaskIdSetterAndGetter(): void
    {
        $taskId = '123456789';

        // 测试设置值
        $this->errorLog->setTaskId($taskId);
        $this->assertSame($taskId, $this->errorLog->getTaskId());

        // 测试设置null
        $this->errorLog->setTaskId(null);
        $this->assertNull($this->errorLog->getTaskId());
    }

    public function testLineSetterAndGetter(): void
    {
        $line = 42;

        // 测试设置值
        $this->errorLog->setLine($line);
        $this->assertSame($line, $this->errorLog->getLine());

        // 测试设置0
        $this->errorLog->setLine(0);
        $this->assertSame(0, $this->errorLog->getLine());

        // 测试设置负数
        $this->errorLog->setLine(-1);
        $this->assertSame(-1, $this->errorLog->getLine());
    }

    public function testErrorSetterAndGetter(): void
    {
        $error = 'Test error message';

        // 测试设置值
        $this->errorLog->setError($error);
        $this->assertSame($error, $this->errorLog->getError());

        // 测试设置null
        $this->errorLog->setError(null);
        $this->assertNull($this->errorLog->getError());
    }

    public function testRawRowSetterAndGetter(): void
    {
        $rawRow = ['column1' => 'value1', 'column2' => 'value2'];

        // 测试设置值
        $this->errorLog->setRawRow($rawRow);
        $this->assertSame($rawRow, $this->errorLog->getRawRow());

        // 测试设置空数组
        $this->errorLog->setRawRow([]);
        $this->assertSame([], $this->errorLog->getRawRow());

        // 测试设置null
        $this->errorLog->setRawRow(null);
        $this->assertNull($this->errorLog->getRawRow());
    }

    public function testNewRowSetterAndGetter(): void
    {
        $newRow = ['field1' => 'value1', 'field2' => 'value2'];

        // 测试设置值
        $this->errorLog->setNewRow($newRow);
        $this->assertSame($newRow, $this->errorLog->getNewRow());

        // 测试设置空数组
        $this->errorLog->setNewRow([]);
        $this->assertSame([], $this->errorLog->getNewRow());

        // 测试设置null
        $this->errorLog->setNewRow(null);
        $this->assertNull($this->errorLog->getNewRow());
    }

    public function testCreateTimeSetterAndGetter(): void
    {
        $now = new \DateTimeImmutable();

        // 测试设置值
        $this->errorLog->setCreateTime($now);
        $this->assertSame($now, $this->errorLog->getCreateTime());

        // 测试设置null
        $this->errorLog->setCreateTime(null);
        $this->assertNull($this->errorLog->getCreateTime());
    }
}
