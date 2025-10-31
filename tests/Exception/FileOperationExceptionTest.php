<?php

declare(strict_types=1);

namespace AsyncImportBundle\Tests\Exception;

use AsyncImportBundle\Exception\FileOperationException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(FileOperationException::class)]
final class FileOperationExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionIsCreatedWithMessage(): void
    {
        $exception = new FileOperationException('File operation failed');

        self::assertSame('File operation failed', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }

    public function testExceptionIsThrowable(): void
    {
        $this->expectException(FileOperationException::class);
        $this->expectExceptionMessage('Test file operation error');

        throw new FileOperationException('Test file operation error');
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new FileOperationException('File operation failed', 500, $previous);

        self::assertSame('File operation failed', $exception->getMessage());
        self::assertSame(500, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }
}
