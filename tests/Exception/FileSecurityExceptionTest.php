<?php

declare(strict_types=1);

namespace AsyncImportBundle\Tests\Exception;

use AsyncImportBundle\Exception\FileSecurityException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(FileSecurityException::class)]
final class FileSecurityExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionIsCreatedWithMessage(): void
    {
        $exception = new FileSecurityException('File security violation');

        self::assertSame('File security violation', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }

    public function testExceptionIsThrowable(): void
    {
        $this->expectException(FileSecurityException::class);
        $this->expectExceptionMessage('Test security error');

        throw new FileSecurityException('Test security error');
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \InvalidArgumentException('Previous error');
        $exception = new FileSecurityException('File security violation', 403, $previous);

        self::assertSame('File security violation', $exception->getMessage());
        self::assertSame(403, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }
}
