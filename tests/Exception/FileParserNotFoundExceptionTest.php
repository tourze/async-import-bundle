<?php

namespace AsyncImportBundle\Tests\Exception;

use AsyncImportBundle\Exception\FileParserNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * FileParserNotFoundException 测试
 *
 * @internal
 */
#[CoversClass(FileParserNotFoundException::class)]
final class FileParserNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInstance(): void
    {
        $exception = new FileParserNotFoundException('Test message');

        $this->assertInstanceOf(FileParserNotFoundException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new FileParserNotFoundException('Test message', 404, $previous);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
