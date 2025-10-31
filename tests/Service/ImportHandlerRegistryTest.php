<?php

namespace AsyncImportBundle\Tests\Service;

use AsyncImportBundle\DTO\ValidationResult;
use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Service\ImportHandlerInterface;
use AsyncImportBundle\Service\ImportHandlerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ImportHandlerRegistry::class)]
final class ImportHandlerRegistryTest extends TestCase
{
    public function testGetHandler(): void
    {
        $userHandler = $this->createMock(ImportHandlerInterface::class);
        $userHandler->method('supports')->willReturnCallback(static function (string $entityClass): bool {
            return 'App\Entity\User' === $entityClass;
        });
        $userHandler->method('getEntityClass')->willReturn('App\Entity\User');

        $productHandler = $this->createMock(ImportHandlerInterface::class);
        $productHandler->method('supports')->willReturnCallback(static function (string $entityClass): bool {
            return 'App\Entity\Product' === $entityClass;
        });
        $productHandler->method('getEntityClass')->willReturn('App\Entity\Product');

        $registry = new ImportHandlerRegistry([$userHandler, $productHandler]);

        $this->assertSame($userHandler, $registry->getHandler('App\Entity\User'));
        $this->assertSame($productHandler, $registry->getHandler('App\Entity\Product'));
    }

    public function testGetHandlerNotFound(): void
    {
        $userHandler = $this->createMock(ImportHandlerInterface::class);
        $userHandler->method('supports')->willReturn(false);
        $userHandler->method('getEntityClass')->willReturn('App\Entity\User');

        $registry = new ImportHandlerRegistry([$userHandler]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No import handler found for entity class: App\Entity\Product');

        $registry->getHandler('App\Entity\Product');
    }

    public function testHasHandler(): void
    {
        $userHandler = $this->createMock(ImportHandlerInterface::class);
        $userHandler->method('supports')->willReturnCallback(static function (string $entityClass): bool {
            return 'App\Entity\User' === $entityClass;
        });
        $userHandler->method('getEntityClass')->willReturn('App\Entity\User');

        $productHandler = $this->createMock(ImportHandlerInterface::class);
        $productHandler->method('supports')->willReturnCallback(static function (string $entityClass): bool {
            return 'App\Entity\Product' === $entityClass;
        });
        $productHandler->method('getEntityClass')->willReturn('App\Entity\Product');

        $duplicateUserHandler = $this->createMock(ImportHandlerInterface::class);
        $duplicateUserHandler->method('supports')->willReturnCallback(static function (string $entityClass): bool {
            return 'App\Entity\User' === $entityClass;
        });
        $duplicateUserHandler->method('getEntityClass')->willReturn('App\Entity\User');

        $registry = new ImportHandlerRegistry([$userHandler, $productHandler, $duplicateUserHandler]);

        $this->assertTrue($registry->hasHandler('App\Entity\User'));
        $this->assertTrue($registry->hasHandler('App\Entity\Product'));
        $this->assertFalse($registry->hasHandler('App\Entity\Order'));
    }

    public function testGetSupportedEntityClasses(): void
    {
        $userHandler = $this->createMock(ImportHandlerInterface::class);
        $userHandler->method('getEntityClass')->willReturn('App\Entity\User');

        $productHandler = $this->createMock(ImportHandlerInterface::class);
        $productHandler->method('getEntityClass')->willReturn('App\Entity\Product');

        $duplicateUserHandler = $this->createMock(ImportHandlerInterface::class);
        $duplicateUserHandler->method('getEntityClass')->willReturn('App\Entity\User');

        $registry = new ImportHandlerRegistry([$userHandler, $productHandler, $duplicateUserHandler]);

        $classes = $registry->getSupportedEntityClasses();

        $this->assertCount(2, $classes);
        $this->assertContains('App\Entity\User', $classes);
        $this->assertContains('App\Entity\Product', $classes);
    }

    public function testGetAllHandlers(): void
    {
        $handler1 = $this->createMock(ImportHandlerInterface::class);
        $handler1->method('getEntityClass')->willReturn('App\Entity\Handler1');

        $handler2 = $this->createMock(ImportHandlerInterface::class);
        $handler2->method('getEntityClass')->willReturn('App\Entity\Handler2');

        $registry = new ImportHandlerRegistry([$handler1, $handler2]);

        $handlers = $registry->getAllHandlers();

        $this->assertCount(2, $handlers);
        $this->assertContains($handler1, $handlers);
        $this->assertContains($handler2, $handlers);
    }
}
