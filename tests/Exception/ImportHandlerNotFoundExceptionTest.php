<?php

namespace AsyncImportBundle\Tests\Exception;

use AsyncImportBundle\Exception\ImportHandlerNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * ImportHandlerNotFoundException 测试
 *
 * @internal
 */
#[CoversClass(ImportHandlerNotFoundException::class)]
final class ImportHandlerNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInstance(): void
    {
        $exception = new ImportHandlerNotFoundException('Test message');

        $this->assertInstanceOf(ImportHandlerNotFoundException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new ImportHandlerNotFoundException('Test message', 404, $previous);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
