<?php

namespace AsyncImportBundle\Tests\DependencyInjection;

use AsyncImportBundle\DependencyInjection\AsyncImportExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AsyncImportExtensionTest extends TestCase
{
    private AsyncImportExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new AsyncImportExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoad_loadsServicesYaml(): void
    {
        // 测试加载配置不会抛出异常
        $this->extension->load([], $this->container);
        
        // 验证服务定义已注册，但不需要编译容器
        $this->assertTrue($this->container->hasDefinition('AsyncImportBundle\Repository\AsyncImportTaskRepository') 
            || $this->container->hasAlias('AsyncImportBundle\Repository\AsyncImportTaskRepository'));
        $this->assertTrue($this->container->hasDefinition('AsyncImportBundle\Repository\AsyncImportErrorLogRepository') 
            || $this->container->hasAlias('AsyncImportBundle\Repository\AsyncImportErrorLogRepository'));
    }
    
    public function testLoad_withEmptyConfiguration(): void
    {
        // 验证加载空配置不会抛出异常
        $this->extension->load([], $this->container);
        $this->addToAssertionCount(1); // 如果没有异常，则测试通过
    }
    
    public function testGetAlias_returnsCorrectName(): void
    {
        // 验证别名是否正确
        $this->assertSame('async_import', $this->extension->getAlias());
    }
} 