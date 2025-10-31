<?php

namespace AsyncImportBundle\DataFixtures;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Enum\ImportTaskStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class AsyncImportTaskFixtures extends Fixture
{
    public const PENDING_TASK_REFERENCE = 'pending-task';
    public const PROCESSING_TASK_REFERENCE = 'processing-task';
    public const COMPLETED_TASK_REFERENCE = 'completed-task';
    public const FAILED_TASK_REFERENCE = 'failed-task';

    public function load(ObjectManager $manager): void
    {
        // 创建待处理任务
        $pendingTask = new AsyncImportTask();
        $pendingTask->setUserId('user_001');
        $pendingTask->setFile('/tmp/test_users.csv');
        $pendingTask->setEntityClass('App\Entity\User');
        $pendingTask->setRemark('测试用户导入');
        $pendingTask->setStatus(ImportTaskStatus::PENDING);
        $pendingTask->setFileType(ImportFileType::CSV);
        $pendingTask->setImportConfig(['headers' => true, 'delimiter' => ',']);
        $pendingTask->setTotalCount(1000);
        $pendingTask->setProcessCount(0);
        $pendingTask->setPriority(100);
        $manager->persist($pendingTask);

        // 创建处理中任务
        $processingTask = new AsyncImportTask();
        $processingTask->setUserId('user_002');
        $processingTask->setFile('/tmp/test_products.xlsx');
        $processingTask->setEntityClass('App\Entity\Product');
        $processingTask->setRemark('产品批量导入');
        $processingTask->setStatus(ImportTaskStatus::PROCESSING);
        $processingTask->setFileType(ImportFileType::EXCEL);
        $processingTask->setImportConfig(['sheet' => 0, 'headers' => true]);
        $processingTask->setTotalCount(500);
        $processingTask->setProcessCount(250);
        $processingTask->setSuccessCount(240);
        $processingTask->setFailCount(10);
        $processingTask->setPriority(90);
        $processingTask->setStartTime(new \DateTimeImmutable('-1 hour'));
        $manager->persist($processingTask);

        // 创建已完成任务
        $completedTask = new AsyncImportTask();
        $completedTask->setUserId('user_003');
        $completedTask->setFile('/tmp/test_orders.json');
        $completedTask->setEntityClass('App\Entity\Order');
        $completedTask->setRemark('订单数据导入');
        $completedTask->setStatus(ImportTaskStatus::COMPLETED);
        $completedTask->setFileType(ImportFileType::JSON);
        $completedTask->setImportConfig(['root_key' => 'orders']);
        $completedTask->setTotalCount(200);
        $completedTask->setProcessCount(200);
        $completedTask->setSuccessCount(195);
        $completedTask->setFailCount(5);
        $completedTask->setPriority(80);
        $completedTask->setStartTime(new \DateTimeImmutable('-2 hours'));
        $completedTask->setEndTime(new \DateTimeImmutable('-1 hour'));
        $manager->persist($completedTask);

        // 创建失败任务
        $failedTask = new AsyncImportTask();
        $failedTask->setUserId('user_004');
        $failedTask->setFile('/tmp/invalid_data.csv');
        $failedTask->setEntityClass('App\Entity\Category');
        $failedTask->setRemark('分类导入失败测试');
        $failedTask->setStatus(ImportTaskStatus::FAILED);
        $failedTask->setFileType(ImportFileType::CSV);
        $failedTask->setImportConfig(['headers' => false]);
        $failedTask->setTotalCount(100);
        $failedTask->setProcessCount(50);
        $failedTask->setSuccessCount(30);
        $failedTask->setFailCount(20);
        $failedTask->setRetryCount(2);
        $failedTask->setMaxRetries(3);
        $failedTask->setPriority(70);
        $failedTask->setStartTime(new \DateTimeImmutable('-3 hours'));
        $failedTask->setLastErrorMessage('数据格式验证失败：必填字段缺失');
        $failedTask->setLastErrorTime(new \DateTimeImmutable('-2 hours 30 minutes'));
        $manager->persist($failedTask);

        $manager->flush();

        // 添加引用
        $this->addReference(self::PENDING_TASK_REFERENCE, $pendingTask);
        $this->addReference(self::PROCESSING_TASK_REFERENCE, $processingTask);
        $this->addReference(self::COMPLETED_TASK_REFERENCE, $completedTask);
        $this->addReference(self::FAILED_TASK_REFERENCE, $failedTask);
    }
}
