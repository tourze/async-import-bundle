<?php

namespace AsyncImportBundle\Tests\Unit\Exception;

use AsyncImportBundle\Exception\ImportTaskException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ImportTaskException 测试
 */
class ImportTaskExceptionTest extends TestCase
{
    public function testExceptionInstance(): void
    {
        $exception = new ImportTaskException('Test message');
        
        $this->assertInstanceOf(ImportTaskException::class, $exception);
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new ImportTaskException('Test message', 500, $previous);
        
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}