<?php

namespace AsyncImportBundle\Tests\Repository;

use AsyncImportBundle\Repository\AsyncImportErrorLogRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class AsyncImportErrorLogRepositoryTest extends TestCase
{
    public function testConstructor_withValidRegistry(): void
    {
        // 模拟 ManagerRegistry
        $registry = $this->createMock(ManagerRegistry::class);
        
        // 创建仓库实例
        $repository = new AsyncImportErrorLogRepository($registry);
        
        // 验证实例类型
        $this->assertInstanceOf(AsyncImportErrorLogRepository::class, $repository);
    }
    
    public function testEntityClass_returnsCorrectClass(): void
    {
        // 直接检查仓库类的构造函数代码
        $fileName = (new \ReflectionClass(AsyncImportErrorLogRepository::class))->getFileName();
        $fileContent = file_get_contents($fileName);
        
        // 检查构造函数是否包含 AsyncImportErrorLog::class
        $this->assertStringContainsString('AsyncImportErrorLog::class', $fileContent);
    }
} 