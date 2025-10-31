<?php

namespace AsyncImportBundle\Tests\Enum;

use AsyncImportBundle\Enum\ImportFileType;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * ImportFileType 枚举测试
 *
 * @internal
 */
#[CoversClass(ImportFileType::class)]
final class ImportFileTypeTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $cases = ImportFileType::cases();
        $this->assertCount(5, $cases);

        $values = array_map(fn (ImportFileType $case) => $case->value, $cases);
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

    public function testExtensions(): void
    {
        $this->assertEquals(['csv'], ImportFileType::CSV->extensions());
        $this->assertEquals(['xls', 'xlsx'], ImportFileType::EXCEL->extensions());
        $this->assertEquals(['xls'], ImportFileType::XLS->extensions());
        $this->assertEquals(['xlsx'], ImportFileType::XLSX->extensions());
        $this->assertEquals(['json'], ImportFileType::JSON->extensions());
    }

    public function testMimeTypes(): void
    {
        $this->assertEquals(['text/csv', 'application/csv'], ImportFileType::CSV->mimeTypes());
        $this->assertEquals(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], ImportFileType::EXCEL->mimeTypes());
        $this->assertEquals(['application/vnd.ms-excel'], ImportFileType::XLS->mimeTypes());
        $this->assertEquals(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], ImportFileType::XLSX->mimeTypes());
        $this->assertEquals(['application/json'], ImportFileType::JSON->mimeTypes());
    }

    public function testToArray(): void
    {
        $result = ImportFileType::CSV->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('csv', $result['value']);
        $this->assertEquals('CSV', $result['label']);

        $result = ImportFileType::EXCEL->toArray();
        $this->assertEquals('excel', $result['value']);
        $this->assertEquals('Excel (自动识别)', $result['label']);
    }
}
