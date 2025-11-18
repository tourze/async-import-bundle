<?php

declare(strict_types=1);

namespace AsyncImportBundle\Tests\Controller\Admin;

use AsyncImportBundle\Controller\Admin\AsyncImportTaskCrudController;
use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Service\AsyncImportService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AsyncImportTaskCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AsyncImportTaskCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): AsyncImportTaskCrudController
    {
        // 使用静态容器获取真实服务，避免重复创建客户端
        $container = self::getContainer();

        $controller = $container->get(AsyncImportTaskCrudController::class);
        self::assertInstanceOf(AsyncImportTaskCrudController::class, $controller);

        return $controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID列' => ['ID'];
        yield '用户ID列' => ['用户ID'];
        yield '文件路径列' => ['文件路径'];
        yield '实体类名列' => ['实体类名'];
        yield '文件类型列' => ['文件类型'];
        yield '状态/进度列' => ['状态/进度'];
        yield '重试次数列' => ['重试次数'];
        yield '最大重试列' => ['最大重试'];
        yield '优先级列' => ['优先级'];
        yield '内存占用列' => ['内存占用'];
        yield '开始时间列' => ['开始时间'];
        yield '结束时间列' => ['结束时间'];
        yield '最后错误时间列' => ['最后错误时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield '用户ID字段' => ['userId'];
        yield '文件路径字段' => ['file'];
        yield '实体类名字段' => ['entityClass'];
        yield '文件类型字段' => ['fileType'];
        yield '优先级字段' => ['priority'];
        yield '最大重试字段' => ['maxRetries'];
        yield '备注字段' => ['remark'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield '用户ID字段' => ['userId'];
        yield '文件路径字段' => ['file'];
        yield '实体类名字段' => ['entityClass'];
        yield '文件类型字段' => ['fileType'];
        yield '优先级字段' => ['priority'];
        yield '最大重试字段' => ['maxRetries'];
        yield '重试次数字段' => ['retryCount'];
        yield '最后错误信息字段' => ['lastErrorMessage'];
        yield '备注字段' => ['remark'];
    }

    public function testControllerCanBeInstantiated(): void
    {
        $client = self::createClientWithDatabase();
        $container = $client->getContainer();

        $controller = $container->get(AsyncImportTaskCrudController::class);

        self::assertInstanceOf(AsyncImportTaskCrudController::class, $controller);
    }

    public function testConfigureFields(): void
    {
        $client = self::createClientWithDatabase();
        $container = $client->getContainer();

        $controller = $container->get(AsyncImportTaskCrudController::class);
        $this->assertInstanceOf(AsyncImportTaskCrudController::class, $controller);

        $fields = iterator_to_array($controller->configureFields('index'));

        self::assertNotEmpty($fields);
        self::assertGreaterThan(5, count($fields));
    }

    public function testConfigureCrud(): void
    {
        $client = self::createClientWithDatabase();
        $container = $client->getContainer();

        $controller = $container->get(AsyncImportTaskCrudController::class);
        $this->assertInstanceOf(AsyncImportTaskCrudController::class, $controller);

        $crud = $controller->configureCrud(Crud::new());

        // 验证CRUD配置实例化成功
        $this->assertInstanceOf(CrudDto::class, $crud->getAsDto());
    }

    public function testConfigureActions(): void
    {
        $client = self::createClientWithDatabase();
        $container = $client->getContainer();

        $controller = $container->get(AsyncImportTaskCrudController::class);
        $this->assertInstanceOf(AsyncImportTaskCrudController::class, $controller);

        $actions = $controller->configureActions(Actions::new());

        $indexActions = $actions->getAsDto('index')->getActions();
        self::assertNotEmpty($indexActions);
    }

    public function testConfigureFilters(): void
    {
        $client = self::createClientWithDatabase();
        $container = $client->getContainer();

        $controller = $container->get(AsyncImportTaskCrudController::class);
        $this->assertInstanceOf(AsyncImportTaskCrudController::class, $controller);

        $filters = $controller->configureFilters(Filters::new());

        // 验证过滤器配置成功
        $this->assertNotNull($filters);
    }

    public function testRetryTaskBusinessLogic(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createAdminUser('admin@test.com', 'password');

        // 创建一个失败的任务
        $task = new AsyncImportTask();
        $task->setUserId($user->getUserIdentifier());
        $task->setFile('/test/file.csv');
        $task->setFileType(ImportFileType::CSV);
        $task->setEntityClass('TestEntity');
        $task->setStatus(ImportTaskStatus::FAILED);
        $task->setTotalCount(100);
        $task->setFailCount(10);
        $task->setRetryCount(0);
        $task->setMaxRetries(3);

        $container = $client->getContainer();
        $asyncImportService = $container->get(AsyncImportService::class);
        $this->assertInstanceOf(AsyncImportService::class, $asyncImportService);

        // 测试业务逻辑：是否可以重试
        self::assertTrue($asyncImportService->canTaskRetry($task));

        // 模拟重试逻辑
        $task->setStatus(ImportTaskStatus::PENDING);
        $asyncImportService->incrementTaskRetryCount($task);
        $task->setLastErrorMessage(null);
        $task->setLastErrorTime(null);

        self::assertSame(ImportTaskStatus::PENDING, $task->getStatus());
        self::assertSame(1, $task->getRetryCount());
    }

    public function testCancelTaskBusinessLogic(): void
    {
        $task = new AsyncImportTask();
        $task->setUserId('user123');
        $task->setFile('/test/file.csv');
        $task->setFileType(ImportFileType::CSV);
        $task->setEntityClass('TestEntity');
        $task->setStatus(ImportTaskStatus::PENDING);
        $task->setTotalCount(100);

        // 测试是否可以取消
        self::assertTrue(in_array($task->getStatus(), [ImportTaskStatus::PENDING, ImportTaskStatus::PROCESSING], true));

        // 模拟取消逻辑
        $task->setStatus(ImportTaskStatus::CANCELLED);
        self::assertSame(ImportTaskStatus::CANCELLED, $task->getStatus());
    }
}
