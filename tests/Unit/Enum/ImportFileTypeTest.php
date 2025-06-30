<?php

namespace AsyncImportBundle\Tests\Unit\Enum;

use AsyncImportBundle\Enum\ImportFileType;
use PHPUnit\Framework\TestCase;

/**
 * ImportFileType 枚举测试
 */
class ImportFileTypeTest extends TestCase
{
    public function testEnumCases(): void
    {
        $cases = ImportFileType::cases();
        $this->assertCount(5, $cases);
        
        $values = array_map(fn(ImportFileType $case) => $case->value, $cases);
        $this->assertContains('csv', $values);
        $this->assertContains('excel', $values);
        $this->assertContains('xls', $values);
        $this->assertContains('xlsx', $values);
        $this->assertContains('json', $values);
    }

    public function testEnumValues(): void
    {
        $this->assertEquals('csv', ImportFileType::CSV->value);
        $this->assertEquals('excel', ImportFileType::EXCEL->value);
        $this->assertEquals('xls', ImportFileType::XLS->value);
        $this->assertEquals('xlsx', ImportFileType::XLSX->value);
        $this->assertEquals('json', ImportFileType::JSON->value);
    }
}