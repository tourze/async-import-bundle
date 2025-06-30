<?php

namespace AsyncImportBundle\Tests\Unit\Exception;

use AsyncImportBundle\Exception\UnsupportedFileTypeException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * UnsupportedFileTypeException 测试
 */
class UnsupportedFileTypeExceptionTest extends TestCase
{
    public function testExceptionInstance(): void
    {
        $exception = new UnsupportedFileTypeException('Test message');
        
        $this->assertInstanceOf(UnsupportedFileTypeException::class, $exception);
        $this->assertInstanceOf(RuntimeException::class, $exception);
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