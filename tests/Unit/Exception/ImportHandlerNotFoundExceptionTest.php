<?php

namespace AsyncImportBundle\Tests\Unit\Exception;

use AsyncImportBundle\Exception\ImportHandlerNotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ImportHandlerNotFoundException 测试
 */
class ImportHandlerNotFoundExceptionTest extends TestCase
{
    public function testExceptionInstance(): void
    {
        $exception = new ImportHandlerNotFoundException('Test message');
        
        $this->assertInstanceOf(ImportHandlerNotFoundException::class, $exception);
        $this->assertInstanceOf(RuntimeException::class, $exception);
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