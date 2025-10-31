<?php

namespace AsyncImportBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class AsyncImportExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
