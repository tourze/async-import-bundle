<?php

namespace AsyncImportBundle\Tests\Exception;

use AsyncImportBundle\Exception\UnsupportedFileTypeException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * UnsupportedFileTypeException 测试
 *
 * @internal
 */
#[CoversClass(UnsupportedFileTypeException::class)]
final class UnsupportedFileTypeExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInstance(): void
    {
        $exception = new UnsupportedFileTypeException('Test message');

        $this->assertInstanceOf(UnsupportedFileTypeException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new UnsupportedFileTypeException('Test message', 400, $previous);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
