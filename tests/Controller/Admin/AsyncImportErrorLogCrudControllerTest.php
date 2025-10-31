<?php

declare(strict_types=1);

namespace AsyncImportBundle\Tests\Controller\Admin;

use AsyncImportBundle\Controller\Admin\AsyncImportErrorLogCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AsyncImportErrorLogCrudController::class)]
#[RunTestsInSeparateProcesses]
class AsyncImportErrorLogCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AsyncImportErrorLogCrudController
     */
    protected function getControllerService(): AbstractCrudController
    {
        $controller = static::getContainer()->get(AsyncImportErrorLogCrudController::class);
        self::assertInstanceOf(AsyncImportErrorLogCrudController::class, $controller);

        return $controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'Task ID' => ['任务ID']; // 使用控制器中实际的中文标签
        yield 'Entity Class' => ['实体类名']; // 使用控制器中实际的中文标签
        yield 'Line Number' => ['行号']; // 使用控制器中实际的中文标签
        yield 'Error Summary' => ['错误摘要']; // 使用控制器中实际的中文标签
        yield 'Created At' => ['创建时间']; // 使用控制器中实际的中文标签
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // NEW action is disabled for error log controllers - return dummy data to prevent empty dataset error
        yield 'dummy' => ['dummy'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // EDIT action is disabled for error log controllers - return dummy data to prevent empty dataset error
        yield 'dummy' => ['dummy'];
    }
}
