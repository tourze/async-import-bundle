# AsyncImportBundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/async-import-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/async-import-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/async-import-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/async-import-bundle)
[![License](https://img.shields.io/packagist/l/tourze/async-import-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/async-import-bundle)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/async-import-bundle?style=flat-square)](https://codecov.io/gh/tourze/async-import-bundle)

A powerful Symfony bundle for handling asynchronous file imports with support for CSV, Excel, 
and JSON formats. Features include progress tracking, error logging, retry mechanisms, and batch processing.

## Table of Contents

- [Features](#features)
- [Dependencies](#dependencies)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Advanced Usage](#advanced-usage)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Features

- **Multiple File Formats**: Support for CSV, Excel (XLS/XLSX), and JSON files
- **Asynchronous Processing**: Uses Symfony Messenger for background processing
- **Progress Tracking**: Real-time progress updates with speed calculation
- **Error Handling**: Detailed error logging with line numbers and data
- **Retry Mechanism**: Automatic retry with exponential backoff
- **Batch Processing**: Configurable batch sizes for memory efficiency
- **Extensible Architecture**: Easy to add custom import handlers
- **EasyAdmin Integration**: Ready for admin panel integration

## Dependencies

### System Requirements

- PHP 8.1 or higher
- Symfony 6.4 or higher
- Doctrine ORM 3.0 or higher

### Required Extensions

- ext-SPL
- ext-date  
- ext-json

### Required Packages

- doctrine/dbal ^4.0
- doctrine/orm ^3.0
- phpoffice/phpspreadsheet ^3.9
- symfony/messenger ^6.4
- And other Symfony components (see composer.json for complete list)

## Installation

```bash
composer require tourze/async-import-bundle
```

## Configuration

### 1. Register the bundle

Register the bundle in your `config/bundles.php`:

```php
return [
    // ...
    AsyncImportBundle\AsyncImportBundle::class => ['all' => true],
    // ...
];
```

### 2. Configure Messenger

Add to `config/packages/messenger.yaml`:

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

### 3. Create upload directory

```bash
mkdir -p var/import
chmod 755 var/import
```

## Usage

### 1. Create an Import Handler

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
            $result->addError('Email is required');
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
            'Email' => 'email',
            'Name' => 'name',
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

### 2. Register the Handler

```yaml
services:
    App\Import\UserImportHandler:
        tags: ['async_import.handler']
```

### 3. Create Import Task

```php
use AsyncImportBundle\Service\AsyncImportService;
use AsyncImportBundle\Message\ProcessImportTaskMessage;

// In your controller
public function import(
    Request $request,
    AsyncImportService $importService,
    MessageBusInterface $messageBus
): Response {
    $file = $request->files->get('file');
    
    // Create import task
    $task = $importService->createTask($file, User::class, [
        'priority' => 10,
        'maxRetries' => 3,
        'remark' => 'User batch import'
    ]);
    
    // Dispatch for async processing
    $messageBus->dispatch(new ProcessImportTaskMessage($task->getId()));
    
    return $this->json(['taskId' => $task->getId()]);
}
```

### 4. Track Progress

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

## File Format Examples

### CSV
```text
Email,Name
john@example.com,John Doe
jane@example.com,Jane Smith
```

### Excel
Standard Excel files with headers in the first row.

### JSON
```json
[
    {"email": "john@example.com", "name": "John Doe"},
    {"email": "jane@example.com", "name": "Jane Smith"}
]
```

## Console Commands

```bash
# Process pending import tasks
php bin/console import:process

# Retry failed tasks
php bin/console import:retry-failed

# Cleanup old tasks (default: 30 days)
php bin/console import:cleanup

# Check import status
php bin/console import:status <taskId>
```

## Error Handling

Errors are logged with detailed information:

```php
$errors = $importService->getTaskErrors($task);

foreach ($errors as $error) {
    echo sprintf(
        "Line %d: %s\nData: %s\n",
        $error->getLine(),
        $error->getError(),
        json_encode($error->getRawRow())
    );
}
```

## Events

The bundle dispatches several events:

- `ImportTaskCreatedEvent`: When a new import task is created
- `ImportProgressEvent`: During import processing with progress updates
- `ImportCompletedEvent`: When import is successfully completed
- `ImportFailedEvent`: When import fails

## Testing

```bash
# Run all tests from project root
./vendor/bin/phpunit packages/async-import-bundle/tests

# Run specific test class
./vendor/bin/phpunit packages/async-import-bundle/tests/Entity/AsyncImportTaskTest.php
```

## Advanced Usage

### Custom File Parsers

Implement `FileParserInterface` to support additional file formats:

```php
class XmlParser implements FileParserInterface
{
    public function supports(ImportFileType $fileType): bool
    {
        return $fileType === ImportFileType::XML;
    }
    
    // ... implement other methods
}
```

### Import Options

```php
$task = $importService->createTask($file, Entity::class, [
    'delimiter' => ';',          // CSV delimiter
    'encoding' => 'ISO-8859-1',  // File encoding
    'skipHeader' => true,        // Skip first row
    'sheetIndex' => 0,          // Excel sheet index
    'rootKey' => 'data',        // JSON root key
    'priority' => 10,           // Task priority
    'maxRetries' => 3,          // Max retry attempts
]);
```

## Performance Tips

1. **Batch Size**: Adjust batch size based on your entity complexity
2. **Memory**: Monitor memory usage for large files
3. **Workers**: Run multiple messenger workers for parallel processing
4. **Indexes**: Ensure database indexes on frequently queried fields

## Contributing

Contributions are welcome! Please ensure all tests pass and add tests for new features.

## License

This package is available under the MIT license.