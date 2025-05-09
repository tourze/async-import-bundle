# AsyncImportBundle

Symfony bundle for asynchronous data import functionality.

## Installation

```bash
composer require tourze/async-import-bundle
```

## Features

- Asynchronous data import for Symfony applications
- Task management for import processes
- Error logging for failed import records

## Configuration

Register the bundle in your `config/bundles.php`:

```php
return [
    // ...
    AsyncImportBundle\AsyncImportBundle::class => ['all' => true],
    // ...
];
```

## Usage

Documentation to be completed

## Testing

The bundle comes with a comprehensive test suite:

```bash
# Run all tests from project root
./vendor/bin/phpunit packages/async-import-bundle/tests

# Run specific test class
./vendor/bin/phpunit packages/async-import-bundle/tests/Entity/AsyncImportTaskTest.php
```

## Contributing

Contributions are welcome!

## License

This package is available under the MIT license.
