<?php

namespace AsyncImportBundle\Tests\Enum;

use AsyncImportBundle\Enum\ImportTaskStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * ImportTaskStatus 枚举测试
 *
 * @internal
 */
#[CoversClass(ImportTaskStatus::class)]
final class ImportTaskStatusTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $cases = ImportTaskStatus::cases();
        $this->assertCount(5, $cases);

        $values = array_map(fn (ImportTaskStatus $case) => $case->value, $cases);
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

    public function testColor(): void
    {
        $this->assertEquals('secondary', ImportTaskStatus::PENDING->color());
        $this->assertEquals('primary', ImportTaskStatus::PROCESSING->color());
        $this->assertEquals('success', ImportTaskStatus::COMPLETED->color());
        $this->assertEquals('danger', ImportTaskStatus::FAILED->color());
        $this->assertEquals('warning', ImportTaskStatus::CANCELLED->color());
    }

    public function testToArray(): void
    {
        $result = ImportTaskStatus::PENDING->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('pending', $result['value']);
        $this->assertEquals('待处理', $result['label']);

        $result = ImportTaskStatus::FAILED->toArray();
        $this->assertEquals('failed', $result['value']);
        $this->assertEquals('失败', $result['label']);
    }
}
