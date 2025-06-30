<?php

namespace AsyncImportBundle\Tests\Unit\Exception;

use AsyncImportBundle\Exception\FileParserNotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * FileParserNotFoundException 测试
 */
class FileParserNotFoundExceptionTest extends TestCase
{
    public function testExceptionInstance(): void
    {
        $exception = new FileParserNotFoundException('Test message');
        
        $this->assertInstanceOf(FileParserNotFoundException::class, $exception);
        $this->assertInstanceOf(RuntimeException::class, $exception);
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