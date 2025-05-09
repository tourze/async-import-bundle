<?php

namespace AsyncImportBundle\Tests\Entity;

use AsyncImportBundle\Entity\AsyncImportErrorLog;
use PHPUnit\Framework\TestCase;

class AsyncImportErrorLogTest extends TestCase
{
    private AsyncImportErrorLog $errorLog;

    protected function setUp(): void
    {
        $this->errorLog = new AsyncImportErrorLog();
    }

    public function testId_defaultValue(): void
    {
        // 初始值应为 0 (根据源码定义)
        $this->assertSame(0, $this->errorLog->getId());
    }

    public function testEntityClass_setterAndGetter(): void
    {
        $className = 'App\Entity\TestEntity';
        
        // 测试设置值
        $this->errorLog->setEntityClass($className);
        $this->assertSame($className, $this->errorLog->getEntityClass());
        
        // 测试设置null
        $this->errorLog->setEntityClass(null);
        $this->assertNull($this->errorLog->getEntityClass());
    }

    public function testTaskId_setterAndGetter(): void
    {
        $taskId = '123456789';
        
        // 测试设置值
        $this->errorLog->setTaskId($taskId);
        $this->assertSame($taskId, $this->errorLog->getTaskId());
        
        // 测试设置null
        $this->errorLog->setTaskId(null);
        $this->assertNull($this->errorLog->getTaskId());
    }

    public function testLine_setterAndGetter(): void
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

    public function testError_setterAndGetter(): void
    {
        $error = 'Test error message';
        
        // 测试设置值
        $this->errorLog->setError($error);
        $this->assertSame($error, $this->errorLog->getError());
        
        // 测试设置null
        $this->errorLog->setError(null);
        $this->assertNull($this->errorLog->getError());
    }

    public function testRawRow_setterAndGetter(): void
    {
        $rawRow = ['column1' => 'value1', 'column2' => 'value2'];
        
        // 测试设置值
        $result = $this->errorLog->setRawRow($rawRow);
        $this->assertSame($this->errorLog, $result);
        $this->assertSame($rawRow, $this->errorLog->getRawRow());
        
        // 测试设置空数组
        $result = $this->errorLog->setRawRow([]);
        $this->assertSame($this->errorLog, $result);
        $this->assertSame([], $this->errorLog->getRawRow());
        
        // 测试设置null
        $result = $this->errorLog->setRawRow(null);
        $this->assertSame($this->errorLog, $result);
        $this->assertNull($this->errorLog->getRawRow());
    }

    public function testNewRow_setterAndGetter(): void
    {
        $newRow = ['field1' => 'value1', 'field2' => 'value2'];
        
        // 测试设置值
        $result = $this->errorLog->setNewRow($newRow);
        $this->assertSame($this->errorLog, $result);
        $this->assertSame($newRow, $this->errorLog->getNewRow());
        
        // 测试设置空数组
        $result = $this->errorLog->setNewRow([]);
        $this->assertSame($this->errorLog, $result);
        $this->assertSame([], $this->errorLog->getNewRow());
        
        // 测试设置null
        $result = $this->errorLog->setNewRow(null);
        $this->assertSame($this->errorLog, $result);
        $this->assertNull($this->errorLog->getNewRow());
    }

    public function testCreateTime_setterAndGetter(): void
    {
        $now = new \DateTime();
        
        // 测试设置值
        $result = $this->errorLog->setCreateTime($now);
        $this->assertSame($this->errorLog, $result);
        $this->assertSame($now, $this->errorLog->getCreateTime());
        
        // 测试设置null
        $result = $this->errorLog->setCreateTime(null);
        $this->assertSame($this->errorLog, $result);
        $this->assertNull($this->errorLog->getCreateTime());
    }
} 