<?php

namespace AsyncImportBundle\DataFixtures;

use AsyncImportBundle\Entity\AsyncImportErrorLog;
use AsyncImportBundle\Entity\AsyncImportTask;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class AsyncImportErrorLogFixtures extends Fixture implements DependentFixtureInterface
{
    public const ERROR_LOG_VALIDATION_REFERENCE = 'error-log-validation';
    public const ERROR_LOG_DATABASE_REFERENCE = 'error-log-database';
    public const ERROR_LOG_FORMAT_REFERENCE = 'error-log-format';

    public function load(ObjectManager $manager): void
    {
        $failedTask = $this->getReference(AsyncImportTaskFixtures::FAILED_TASK_REFERENCE, AsyncImportTask::class);
        $processingTask = $this->getReference(AsyncImportTaskFixtures::PROCESSING_TASK_REFERENCE, AsyncImportTask::class);

        // 创建验证错误日志
        $validationError = new AsyncImportErrorLog();
        $validationError->setEntityClass('App\Entity\Category');
        $validationError->setTaskId($failedTask->getId());
        $validationError->setLine(15);
        $validationError->setError('验证失败：name字段不能为空');
        $validationError->setRawRow(['id' => '15', 'name' => '', 'description' => '空名称分类']);
        $validationError->setNewRow(['id' => 15, 'name' => null, 'description' => '空名称分类']);
        $manager->persist($validationError);

        // 创建数据库错误日志
        $databaseError = new AsyncImportErrorLog();
        $databaseError->setEntityClass('App\Entity\Category');
        $databaseError->setTaskId($failedTask->getId());
        $databaseError->setLine(32);
        $databaseError->setError('数据库约束违反：UNIQUE constraint failed: category.slug');
        $databaseError->setRawRow(['id' => '32', 'name' => '重复分类', 'slug' => 'duplicate-category']);
        $databaseError->setNewRow(['id' => 32, 'name' => '重复分类', 'slug' => 'duplicate-category']);
        $manager->persist($databaseError);

        // 创建格式错误日志
        $formatError = new AsyncImportErrorLog();
        $formatError->setEntityClass('App\Entity\Product');
        $formatError->setTaskId($processingTask->getId());
        $formatError->setLine(127);
        $formatError->setError('数据格式错误：价格字段必须为数字，实际值：invalid_price');
        $formatError->setRawRow(['id' => '127', 'name' => '测试产品', 'price' => 'invalid_price']);
        $formatError->setNewRow(['id' => 127, 'name' => '测试产品', 'price' => null]);
        $manager->persist($formatError);

        // 创建类型转换错误日志
        $typeError = new AsyncImportErrorLog();
        $typeError->setEntityClass('App\Entity\Product');
        $typeError->setTaskId($processingTask->getId());
        $typeError->setLine(156);
        $typeError->setError('类型转换失败：无法将字符串"2024-99-99"转换为日期');
        $typeError->setRawRow(['id' => '156', 'name' => '过期产品', 'expired_at' => '2024-99-99']);
        $typeError->setNewRow(['id' => 156, 'name' => '过期产品', 'expired_at' => null]);
        $manager->persist($typeError);

        // 创建关联数据错误日志
        $relationError = new AsyncImportErrorLog();
        $relationError->setEntityClass('App\Entity\Product');
        $relationError->setTaskId($processingTask->getId());
        $relationError->setLine(203);
        $relationError->setError('关联数据不存在：分类ID 9999 不存在');
        $relationError->setRawRow(['id' => '203', 'name' => '无分类产品', 'category_id' => '9999']);
        $relationError->setNewRow(['id' => 203, 'name' => '无分类产品', 'category_id' => 9999]);
        $manager->persist($relationError);

        $manager->flush();

        // 添加引用
        $this->addReference(self::ERROR_LOG_VALIDATION_REFERENCE, $validationError);
        $this->addReference(self::ERROR_LOG_DATABASE_REFERENCE, $databaseError);
        $this->addReference(self::ERROR_LOG_FORMAT_REFERENCE, $formatError);
    }

    public function getDependencies(): array
    {
        return [
            AsyncImportTaskFixtures::class,
        ];
    }
}
