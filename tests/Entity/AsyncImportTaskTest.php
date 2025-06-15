<?php

namespace AsyncImportBundle\Tests\Entity;

use AsyncImportBundle\Entity\AsyncImportTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class AsyncImportTaskTest extends TestCase
{
    private AsyncImportTask $task;

    protected function setUp(): void
    {
        $this->task = new AsyncImportTask();
    }

    public function testId_defaultValue(): void
    {
        // 测试初始值为null
        $this->assertNull($this->task->getId());
    }

    public function testUser_setterAndGetter(): void
    {
        // 创建模拟用户对象
        $mockUser = $this->createMock(UserInterface::class);
        $mockUser->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('test-user-id');
        
        // 测试通过 UserInterface 设置
        $this->assertSame($this->task, $this->task->setUser($mockUser));
        $this->assertSame('test-user-id', $this->task->getUserId());
        
        // 测试直接设置用户ID
        $this->assertSame($this->task, $this->task->setUserId('another-user-id'));
        $this->assertSame('another-user-id', $this->task->getUserId());
        
        // 测试设置null
        $this->assertSame($this->task, $this->task->setUser(null));
        $this->assertNull($this->task->getUserId());
    }

    public function testFile_setterAndGetter(): void
    {
        $fileName = 'test-file.xlsx';
        
        // 测试设置值
        $this->assertSame($this->task, $this->task->setFile($fileName));
        $this->assertSame($fileName, $this->task->getFile());
    }

    public function testEntityClass_setterAndGetter(): void
    {
        $className = 'App\Entity\TestEntity';
        
        // 测试设置值
        $this->assertSame($this->task, $this->task->setEntityClass($className));
        $this->assertSame($className, $this->task->getEntityClass());
    }

    public function testRemark_setterAndGetter(): void
    {
        $remark = 'Test remark content';
        
        // 测试设置值
        $this->assertSame($this->task, $this->task->setRemark($remark));
        $this->assertSame($remark, $this->task->getRemark());
        
        // 测试设置null
        $this->assertSame($this->task, $this->task->setRemark(null));
        $this->assertNull($this->task->getRemark());
    }

    public function testLastErrorMessage_setterAndGetter(): void
    {
        $errorMsg = 'Test error message';
        
        // 测试设置值
        $this->assertSame($this->task, $this->task->setLastErrorMessage($errorMsg));
        $this->assertSame($errorMsg, $this->task->getLastErrorMessage());
        
        // 测试设置null
        $this->assertSame($this->task, $this->task->setLastErrorMessage(null));
        $this->assertNull($this->task->getLastErrorMessage());
    }

    public function testLastErrorTime_setterAndGetter(): void
    {
        $now = new \DateTime();
        
        // 测试设置值
        $this->assertSame($this->task, $this->task->setLastErrorTime($now));
        $this->assertSame($now, $this->task->getLastErrorTime());
        
        // 测试设置null
        $this->assertSame($this->task, $this->task->setLastErrorTime(null));
        $this->assertNull($this->task->getLastErrorTime());
    }

    public function testTotalCount_setterAndGetter(): void
    {
        $count = 1000;
        
        // 测试设置值
        $this->assertSame($this->task, $this->task->setTotalCount($count));
        $this->assertSame($count, $this->task->getTotalCount());
        
        // 测试设置零值
        $this->assertSame($this->task, $this->task->setTotalCount(0));
        $this->assertSame(0, $this->task->getTotalCount());
        
        // 测试设置负值
        $this->assertSame($this->task, $this->task->setTotalCount(-10));
        $this->assertSame(-10, $this->task->getTotalCount());
        
        // 测试设置null
        $this->assertSame($this->task, $this->task->setTotalCount(null));
        $this->assertNull($this->task->getTotalCount());
    }

    public function testProcessCount_setterAndGetter(): void
    {
        $count = 500;
        
        // 测试设置值
        $this->assertSame($this->task, $this->task->setProcessCount($count));
        $this->assertSame($count, $this->task->getProcessCount());
        
        // 测试设置零值
        $this->assertSame($this->task, $this->task->setProcessCount(0));
        $this->assertSame(0, $this->task->getProcessCount());
        
        // 测试设置负值
        $this->assertSame($this->task, $this->task->setProcessCount(-5));
        $this->assertSame(-5, $this->task->getProcessCount());
        
        // 测试设置null
        $this->assertSame($this->task, $this->task->setProcessCount(null));
        $this->assertNull($this->task->getProcessCount());
    }

    public function testMemoryUsage_setterAndGetter(): void
    {
        $memory = 1048576; // 1MB
        
        // 测试设置值
        $this->assertSame($this->task, $this->task->setMemoryUsage($memory));
        $this->assertSame($memory, $this->task->getMemoryUsage());
        
        // 测试设置零值
        $this->assertSame($this->task, $this->task->setMemoryUsage(0));
        $this->assertSame(0, $this->task->getMemoryUsage());
        
        // 测试设置null
        $this->assertSame($this->task, $this->task->setMemoryUsage(null));
        $this->assertNull($this->task->getMemoryUsage());
    }

    public function testCreateTime_setterAndGetter(): void
    {
        $now = new \DateTime();
        
        // 测试设置值
        $this->task->setCreateTime($now);
        $this->assertSame($now, $this->task->getCreateTime());
        
        // 测试设置null
        $this->task->setCreateTime(null);
        $this->assertNull($this->task->getCreateTime());
    }

    public function testUpdateTime_setterAndGetter(): void
    {
        $now = new \DateTime();
        
        // 测试设置值
        $this->task->setUpdateTime($now);
        $this->assertSame($now, $this->task->getUpdateTime());
        
        // 测试设置null
        $this->task->setUpdateTime(null);
        $this->assertNull($this->task->getUpdateTime());
    }

    public function testCreatedBy_setterAndGetter(): void
    {
        $userId = 'user123';
        
        // 测试设置值
        $this->assertSame($this->task, $this->task->setCreatedBy($userId));
        $this->assertSame($userId, $this->task->getCreatedBy());
        
        // 测试设置null
        $this->assertSame($this->task, $this->task->setCreatedBy(null));
        $this->assertNull($this->task->getCreatedBy());
    }

    public function testUpdatedBy_setterAndGetter(): void
    {
        $userId = 'user456';
        
        // 测试设置值
        $this->assertSame($this->task, $this->task->setUpdatedBy($userId));
        $this->assertSame($userId, $this->task->getUpdatedBy());
        
        // 测试设置null
        $this->assertSame($this->task, $this->task->setUpdatedBy(null));
        $this->assertNull($this->task->getUpdatedBy());
    }
} 