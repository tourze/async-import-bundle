<?php

namespace AsyncImportBundle\Service;

/**
 * 数据验证结果
 */
class ValidationResult
{
    private bool $valid;
    private array $errors = [];
    private array $warnings = [];

    public function __construct(bool $valid = true)
    {
        $this->valid = $valid;
    }

    public static function success(): self
    {
        return new self(true);
    }

    public static function failure(string $error): self
    {
        $result = new self(false);
        $result->addError($error);
        return $result;
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;
        $this->valid = false;
        return $this;
    }

    public function isValid(): bool
    {
        return $this->valid && empty($this->errors);
    }

    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;
        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getErrorMessage(): string
    {
        return implode('; ', $this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
}