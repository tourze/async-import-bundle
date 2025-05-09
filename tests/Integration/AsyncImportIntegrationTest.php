<?php

namespace AsyncImportBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AsyncImportIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return IntegrationTestKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel(['debug' => false]);
    }

    public function testServiceWiring_repositoriesAreRegistered(): void
    {
        $container = self::getContainer();

        // 测试服务是否已注册
        $this->assertTrue($container->has('AsyncImportBundle\Repository\AsyncImportTaskRepository'));
        $this->assertTrue($container->has('AsyncImportBundle\Repository\AsyncImportErrorLogRepository'));
    }
} 