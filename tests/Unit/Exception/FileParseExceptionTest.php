<?php

namespace AsyncImportBundle\Tests\Unit\Exception;

use AsyncImportBundle\Exception\FileParseException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * FileParseException 测试
 */
class FileParseExceptionTest extends TestCase
{
    public function testExceptionInstance(): void
    {
        $exception = new FileParseException('Test message');
        
        $this->assertInstanceOf(FileParseException::class, $exception);
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new FileParseException('Test message', 500, $previous);
        
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}