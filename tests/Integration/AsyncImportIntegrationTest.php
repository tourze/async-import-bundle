<?php

namespace AsyncImportBundle\Tests\Integration;

use AsyncImportBundle\AsyncImportBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;

class AsyncImportIntegrationTest extends KernelTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new IntegrationTestKernel($env, $debug, [
            AsyncImportBundle::class => ['all' => true],
        ]);
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