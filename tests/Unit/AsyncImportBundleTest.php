<?php

namespace AsyncImportBundle\Tests\Unit;

use AsyncImportBundle\AsyncImportBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * AsyncImportBundle 测试
 */
class AsyncImportBundleTest extends TestCase
{
    public function testBundleInstance(): void
    {
        $bundle = new AsyncImportBundle();
        $this->assertInstanceOf(AsyncImportBundle::class, $bundle);
    }

    public function testBundleBuild(): void
    {
        $bundle = new AsyncImportBundle();
        $container = new ContainerBuilder();
        
        $bundle->build($container);
        
        $this->assertInstanceOf(ContainerBuilder::class, $container);
    }
}