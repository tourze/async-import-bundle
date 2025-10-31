<?php

namespace AsyncImportBundle\Tests\DependencyInjection;

use AsyncImportBundle\DependencyInjection\AsyncImportExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(AsyncImportExtension::class)]
final class AsyncImportExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private AsyncImportExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new AsyncImportExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testGetAliasReturnsCorrectName(): void
    {
        $this->assertSame('async_import', $this->extension->getAlias());
    }
}
