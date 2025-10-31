<?php

namespace AsyncImportBundle\Tests\DTO;

use AsyncImportBundle\DTO\ValidationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ValidationResult::class)]
final class ValidationResultTest extends TestCase
{
    public function testSuccessFactory(): void
    {
        $result = ValidationResult::success();

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
        $this->assertEmpty($result->getWarnings());
        $this->assertFalse($result->hasWarnings());
    }

    public function testFailureFactory(): void
    {
        $result = ValidationResult::failure('Something went wrong');

        $this->assertFalse($result->isValid());
        $this->assertSame(['Something went wrong'], $result->getErrors());
        $this->assertSame('Something went wrong', $result->getErrorMessage());
    }

    public function testAddError(): void
    {
        $result = new ValidationResult(true);

        $this->assertTrue($result->isValid());

        $result->addError('First error');
        $result->addError('Second error');

        $this->assertFalse($result->isValid());
        $this->assertSame(['First error', 'Second error'], $result->getErrors());
        $this->assertSame('First error; Second error', $result->getErrorMessage());
    }

    public function testAddWarning(): void
    {
        $result = ValidationResult::success();

        $result->addWarning('Warning 1');
        $result->addWarning('Warning 2');

        $this->assertTrue($result->isValid());
        $this->assertTrue($result->hasWarnings());
        $this->assertSame(['Warning 1', 'Warning 2'], $result->getWarnings());
    }

    public function testMixedErrorsAndWarnings(): void
    {
        $result = new ValidationResult();

        $result->addWarning('This is a warning');
        $result->addError('This is an error');
        $result->addWarning('Another warning');

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasWarnings());
        $this->assertCount(1, $result->getErrors());
        $this->assertCount(2, $result->getWarnings());
    }
}
