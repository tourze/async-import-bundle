# 异步导入模块使用示例

## 1. 实现自定义导入处理器

```php
<?php

namespace App\Import;

use AsyncImportBundle\DTO\ValidationResult;use AsyncImportBundle\Entity\AsyncImportTask;use AsyncImportBundle\Service\ImportHandlerInterface;

class ProductImportHandler implements ImportHandlerInterface
{
    public function supports(string $entityClass): bool
    {
        return $entityClass === Product::class;
    }

    public function validate(array $row, int $lineNumber): ValidationResult
    {
        $result = ValidationResult::success();
        
        if (empty($row['sku'])) {
            $result->addError('SKU不能为空');
        }
        
        if (empty($row['name'])) {
            $result->addError('产品名称不能为空');
        }
        
        if (!is_numeric($row['price']) || $row['price'] < 0) {
            $result->addError('价格必须是正数');
        }
        
        return $result;
    }

    public function import(array $row, AsyncImportTask $task): void
    {
        $product = new Product();
        $product->setSku($row['sku']);
        $product->setName($row['name']);
        $product->setPrice((float) $row['price']);
        $product->setStock((int) ($row['stock'] ?? 0));
        
        $this->entityManager->persist($product);
    }

    public function getFieldMapping(): array
    {
        return [
            'SKU' => 'sku',
            '产品名称' => 'name',
            '价格' => 'price',
            '库存' => 'stock'
        ];
    }

    public function getBatchSize(): int
    {
        return 500;
    }

    public function getEntityClass(): string
    {
        return Product::class;
    }

    public function preprocess(array $row): array
    {
        // 预处理数据
        foreach ($row as $key => $value) {
            $row[$key] = trim($value);
        }
        return $row;
    }
}
```

## 2. 注册处理器

在 `config/services.yaml` 中注册：

```yaml
services:
    App\Import\ProductImportHandler:
        tags: ['async_import.handler']
```

## 3. 控制器中使用

```php
<?php

namespace App\Controller;

use AsyncImportBundle\Service\AsyncImportService;
use AsyncImportBundle\Message\ProcessImportTaskMessage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class ImportController extends AbstractController
{
    #[Route('/import/products', name: 'import_products', methods: ['POST'])]
    public function importProducts(
        Request $request,
        AsyncImportService $importService,
        MessageBusInterface $messageBus
    ): Response {
        $file = $request->files->get('file');
        
        if (!$file) {
            return $this->json(['error' => '请上传文件'], 400);
        }
        
        // 创建导入任务
        $task = $importService->createTask(
            $file,
            Product::class,
            [
                'priority' => 10,
                'maxRetries' => 3,
                'remark' => '产品批量导入'
            ]
        );
        
        // 发送异步处理消息
        $messageBus->dispatch(new ProcessImportTaskMessage($task->getId()));
        
        return $this->json([
            'taskId' => $task->getId(),
            'message' => '导入任务已创建，正在后台处理'
        ]);
    }
    
    #[Route('/import/progress/{taskId}', name: 'import_progress')]
    public function getProgress(
        string $taskId,
        AsyncImportTaskRepository $taskRepository,
        ImportProgressTracker $progressTracker
    ): Response {
        $task = $taskRepository->find($taskId);
        
        if (!$task) {
            return $this->json(['error' => '任务不存在'], 404);
        }
        
        $progress = $progressTracker->getProgress($task);
        
        return $this->json([
            'status' => $task->getStatus()->value,
            'progress' => $progress
        ]);
    }
}
```

## 4. 配置 Messenger

在 `config/packages/messenger.yaml` 中配置：

```yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: import
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2

        routing:
            AsyncImportBundle\Message\ProcessImportTaskMessage: async
            AsyncImportBundle\Message\ProcessImportBatchMessage: async
            AsyncImportBundle\Message\CleanupImportTaskMessage: async
```

## 5. 创建导入模板

创建 CSV 模板文件：

```csv
SKU,产品名称,价格,库存
PROD001,示例产品1,99.99,100
PROD002,示例产品2,149.99,50
PROD003,示例产品3,199.99,30
```

## 6. 处理导入事件

监听导入进度事件：

```php
<?php

namespace App\EventListener;

use AsyncImportBundle\Event\ImportProgressEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ImportProgressListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ImportProgressEvent::class => 'onProgress',
        ];
    }

    public function onProgress(ImportProgressEvent $event): void
    {
        $task = $event->getTask();
        
        // 可以在这里发送进度通知，比如 WebSocket 推送
        $this->logger->info('Import progress', [
            'taskId' => $task->getId(),
            'percentage' => $event->getPercentage(),
            'speed' => $event->getSpeed()
        ]);
    }
}
```

## 7. 命令行导入

使用命令行触发导入：

```bash
# 处理待处理的导入任务
php bin/console import:process

# 重试失败的任务
php bin/console import:retry-failed

# 清理过期任务（30天前的）
php bin/console import:cleanup
```

## 8. 错误处理

查看导入错误：

```php
// 获取任务的错误日志
$errors = $importService->getTaskErrors($task, 100, 0);

foreach ($errors as $error) {
    echo sprintf(
        "行 %d: %s\n原始数据: %s\n",
        $error->getLine(),
        $error->getError(),
        json_encode($error->getRawRow())
    );
}
```