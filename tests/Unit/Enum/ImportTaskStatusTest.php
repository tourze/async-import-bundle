<?php

namespace AsyncImportBundle\Tests\Unit\Enum;

use AsyncImportBundle\Enum\ImportTaskStatus;
use PHPUnit\Framework\TestCase;

/**
 * ImportTaskStatus 枚举测试
 */
class ImportTaskStatusTest extends TestCase
{
    public function testEnumCases(): void
    {
        $cases = ImportTaskStatus::cases();
        $this->assertCount(5, $cases);
        
        $values = array_map(fn(ImportTaskStatus $case) => $case->value, $cases);
        $this->assertContains('pending', $values);
        $this->assertContains('processing', $values);
        $this->assertContains('completed', $values);
        $this->assertContains('failed', $values);
        $this->assertContains('cancelled', $values);
    }

    public function testEnumValues(): void
    {
        $this->assertEquals('pending', ImportTaskStatus::PENDING->value);
        $this->assertEquals('processing', ImportTaskStatus::PROCESSING->value);
        $this->assertEquals('completed', ImportTaskStatus::COMPLETED->value);
        $this->assertEquals('failed', ImportTaskStatus::FAILED->value);
        $this->assertEquals('cancelled', ImportTaskStatus::CANCELLED->value);
    }
}