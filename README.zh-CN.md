# AsyncImportBundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/async-import-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/async-import-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/async-import-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/async-import-bundle)
[![License](https://img.shields.io/packagist/l/tourze/async-import-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/async-import-bundle)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/async-import-bundle?style=flat-square)](https://codecov.io/gh/tourze/async-import-bundle)

一个功能强大的 Symfony Bundle，用于处理异步文件导入，支持 CSV、Excel 和 JSON 格式。
包含进度跟踪、错误日志、重试机制和批处理等功能。

## 目录

- [功能特性](#功能特性)
- [依赖要求](#依赖要求)
- [安装](#安装)
- [配置](#配置)
- [使用方法](#使用方法)
- [高级用法](#高级用法)
- [测试](#测试)
- [贡献](#贡献)
- [许可证](#许可证)

## 功能特性

- **多种文件格式**：支持 CSV、Excel（XLS/XLSX）和 JSON 文件
- **异步处理**：使用 Symfony Messenger 进行后台处理
- **进度跟踪**：实时进度更新，包含处理速度计算
- **错误处理**：详细的错误日志，包含行号和数据
- **重试机制**：自动重试，支持指数退避算法
- **批量处理**：可配置的批次大小，提高内存效率
- **可扩展架构**：轻松添加自定义导入处理器
- **EasyAdmin 集成**：可直接集成到管理后台

## 依赖要求

### 系统要求

- PHP 8.1 或更高版本
- Symfony 6.4 或更高版本
- Doctrine ORM 3.0 或更高版本

### 必需的扩展

- ext-SPL
- ext-date  
- ext-json

### 必需的包

- doctrine/dbal ^4.0
- doctrine/orm ^3.0
- phpoffice/phpspreadsheet ^3.9
- symfony/messenger ^6.4
- 以及其他 Symfony 组件（完整列表请查看 composer.json）

## 安装

```bash
composer require tourze/async-import-bundle
```

## 配置

### 1. 注册 Bundle

在 `config/bundles.php` 中注册：

```php
return [
    // ...
    AsyncImportBundle\AsyncImportBundle::class => ['all' => true],
    // ...
];
```

### 2. 配置 Messenger

在 `config/packages/messenger.yaml` 中添加：

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

### 3. 创建上传目录

```bash
mkdir -p var/import
chmod 755 var/import
```

## 使用方法

### 1. 创建导入处理器

```php
<?php

namespace App\Import;

use AsyncImportBundle\DTO\ValidationResult;use AsyncImportBundle\Entity\AsyncImportTask;use AsyncImportBundle\Service\ImportHandlerInterface;

class UserImportHandler implements ImportHandlerInterface
{
    public function supports(string $entityClass): bool
    {
        return $entityClass === User::class;
    }

    public function validate(array $row, int $lineNumber): ValidationResult
    {
        $result = ValidationResult::success();
        
        if (empty($row['email'])) {
            $result->addError('邮箱不能为空');
        }
        
        return $result;
    }

    public function import(array $row, AsyncImportTask $task): void
    {
        $user = new User();
        $user->setEmail($row['email']);
        $user->setName($row['name']);
        
        $this->entityManager->persist($user);
    }

    public function getFieldMapping(): array
    {
        return [
            '邮箱' => 'email',
            '姓名' => 'name',
        ];
    }

    public function getBatchSize(): int
    {
        return 100;
    }

    public function getEntityClass(): string
    {
        return User::class;
    }

    public function preprocess(array $row): array
    {
        return array_map('trim', $row);
    }
}
```

### 2. 注册处理器

```yaml
services:
    App\Import\UserImportHandler:
        tags: ['async_import.handler']
```

### 3. 创建导入任务

```php
use AsyncImportBundle\Service\AsyncImportService;
use AsyncImportBundle\Message\ProcessImportTaskMessage;

// 在控制器中
public function import(
    Request $request,
    AsyncImportService $importService,
    MessageBusInterface $messageBus
): Response {
    $file = $request->files->get('file');
    
    // 创建导入任务
    $task = $importService->createTask($file, User::class, [
        'priority' => 10,
        'maxRetries' => 3,
        'remark' => '用户批量导入'
    ]);
    
    // 分发异步处理
    $messageBus->dispatch(new ProcessImportTaskMessage($task->getId()));
    
    return $this->json(['taskId' => $task->getId()]);
}
```

### 4. 跟踪进度

```php
use AsyncImportBundle\Service\ImportProgressTracker;

public function progress(
    string $taskId,
    ImportProgressTracker $progressTracker
): Response {
    $progress = $progressTracker->getProgress($task);
    
    return $this->json([
        'percentage' => $progress['percentage'],
        'processed' => $progress['processed'],
        'total' => $progress['total'],
        'speed' => $progress['speed'],
        'eta' => $progress['eta']
    ]);
}
```

## 文件格式示例

### CSV
```text
邮箱,姓名
john@example.com,张三
jane@example.com,李四
```

### Excel
标准 Excel 文件，第一行为表头。

### JSON
```json
[
    {"email": "john@example.com", "name": "张三"},
    {"email": "jane@example.com", "name": "李四"}
]
```

## 控制台命令

```bash
# 处理待处理的导入任务
php bin/console import:process

# 重试失败的任务
php bin/console import:retry-failed

# 清理过期任务（默认：30天）
php bin/console import:cleanup

# 查看导入状态
php bin/console import:status <taskId>
```

## 错误处理

错误会被详细记录：

```php
$errors = $importService->getTaskErrors($task);

foreach ($errors as $error) {
    echo sprintf(
        "行 %d: %s\n数据: %s\n",
        $error->getLine(),
        $error->getError(),
        json_encode($error->getRawRow())
    );
}
```

## 事件

Bundle 会分发以下事件：

- `ImportTaskCreatedEvent`：创建新导入任务时
- `ImportProgressEvent`：导入处理过程中的进度更新
- `ImportCompletedEvent`：导入成功完成时
- `ImportFailedEvent`：导入失败时

## 测试

```bash
# 从项目根目录运行所有测试
./vendor/bin/phpunit packages/async-import-bundle/tests

# 运行特定测试类
./vendor/bin/phpunit packages/async-import-bundle/tests/Entity/AsyncImportTaskTest.php
```

测试覆盖以下方面：
- 实体类测试：所有属性的getter/setter方法
- 仓库类测试：确保仓库类正确初始化
- 依赖注入测试：确保服务配置正确加载
- 集成测试：验证服务注册和容器配置

## 高级用法

### 自定义文件解析器

实现 `FileParserInterface` 以支持其他文件格式：

```php
class XmlParser implements FileParserInterface
{
    public function supports(ImportFileType $fileType): bool
    {
        return $fileType === ImportFileType::XML;
    }
    
    // ... 实现其他方法
}
```

### 导入选项

```php
$task = $importService->createTask($file, Entity::class, [
    'delimiter' => ';',          // CSV 分隔符
    'encoding' => 'GBK',         // 文件编码
    'skipHeader' => true,        // 跳过第一行
    'sheetIndex' => 0,          // Excel 工作表索引
    'rootKey' => 'data',        // JSON 根键
    'priority' => 10,           // 任务优先级
    'maxRetries' => 3,          // 最大重试次数
]);
```

## 性能优化建议

1. **批次大小**：根据实体复杂度调整批次大小
2. **内存监控**：处理大文件时注意内存使用
3. **并行处理**：运行多个 messenger worker 实现并行处理
4. **数据库索引**：确保频繁查询的字段有索引

## 参考文档

- [Symfony Bundle开发文档](https://symfony.com/doc/current/bundles.html)
- [Doctrine ORM文档](https://www.doctrine-project.org/projects/doctrine-orm/en/2.14/index.html)
- [Symfony Messenger文档](https://symfony.com/doc/current/messenger.html)
- [PhpSpreadsheet文档](https://phpspreadsheet.readthedocs.io/)

## 贡献

欢迎贡献代码！请确保所有测试通过，并为新功能添加测试。

## 许可证

本包基于 MIT 许可证发布。