<?php

declare(strict_types=1);

namespace AsyncImportBundle\Tests;

use AsyncImportBundle\AsyncImportBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(AsyncImportBundle::class)]
#[RunTestsInSeparateProcesses]
final class AsyncImportBundleTest extends AbstractBundleTestCase
{
}
