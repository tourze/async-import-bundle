<?php

namespace AsyncImportBundle\Service\Parser;

use AsyncImportBundle\DTO\ValidationResult;
use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Exception\FileParseException;
use AsyncImportBundle\Service\FileParserInterface;

/**
 * JSON 文件解析器
 */
class JsonParser implements FileParserInterface
{
    public function supports(ImportFileType $fileType): bool
    {
        return ImportFileType::JSON === $fileType;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function parse(string $filePath, array $options = []): \Iterator
    {
        $this->validateFileExists($filePath);

        $rootKey = isset($options['rootKey']) && is_string($options['rootKey']) ? $options['rootKey'] : null;
        $data = $this->loadJsonData($filePath, $rootKey);
        $data = $this->normalizeToIndexedArray($data);

        foreach ($data as $index => $row) {
            $this->validateRowIsArray($row, $index);
            yield $row;
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function countRows(string $filePath, array $options = []): int
    {
        $rootKey = isset($options['rootKey']) && is_string($options['rootKey']) ? $options['rootKey'] : null;

        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }

        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new FileParseException(sprintf('Failed to read file: %s', $filePath));
        }

        $data = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new FileParseException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }

        // 如果指定了根键，获取该键的数据
        if (null !== $rootKey && is_array($data) && array_key_exists($rootKey, $data)) {
            $data = $data[$rootKey];
        }

        if (!is_array($data)) {
            return 0;
        }

        // 如果不是索引数组，算作一行
        if (!isset($data[0])) {
            return 1;
        }

        return count($data);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string>
     */
    public function getHeaders(string $filePath, array $options = []): array
    {
        $rootKey = isset($options['rootKey']) && is_string($options['rootKey']) ? $options['rootKey'] : null;

        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }

        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new FileParseException(sprintf('Failed to read file: %s', $filePath));
        }

        $data = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new FileParseException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }

        // 如果指定了根键，获取该键的数据
        if (null !== $rootKey && is_array($data) && array_key_exists($rootKey, $data)) {
            $data = $data[$rootKey];
        }

        if (!is_array($data)) {
            return [];
        }

        // 获取第一个元素的键作为表头
        $firstRow = $data[0] ?? $data;

        if (!is_array($firstRow)) {
            return [];
        }

        return array_keys($firstRow);
    }

    public function validateFormat(string $filePath): ValidationResult
    {
        if (!file_exists($filePath)) {
            return ValidationResult::failure('文件不存在');
        }

        if (!is_readable($filePath)) {
            return ValidationResult::failure('文件不可读');
        }

        $fileSize = filesize($filePath);
        if (0 === $fileSize) {
            return ValidationResult::failure('文件为空');
        }

        // 检查文件扩展名
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ('json' !== $extension) {
            return ValidationResult::failure(sprintf('文件扩展名不正确: %s', $extension));
        }

        // 尝试解析JSON验证格式
        $content = file_get_contents($filePath);
        if (false === $content) {
            return ValidationResult::failure('无法读取文件内容');
        }

        json_decode($content);

        if (JSON_ERROR_NONE !== json_last_error()) {
            return ValidationResult::failure(sprintf('无效的JSON格式: %s', json_last_error_msg()));
        }

        return ValidationResult::success();
    }

    private function validateFileExists(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function loadJsonData(string $filePath, ?string $rootKey): array
    {
        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new FileParseException(sprintf('Failed to read file: %s', $filePath));
        }

        $data = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new FileParseException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }

        // 如果指定了根键，获取该键的数据
        if (null !== $rootKey && is_array($data) && isset($data[$rootKey])) {
            $data = $data[$rootKey];
        }

        if (!is_array($data)) {
            throw new FileParseException('JSON data is not an array');
        }

        /** @var array<string, mixed> */
        return $data;
    }

    /**
     * @param mixed $data
     * @return array<array<string, mixed>>
     */
    private function normalizeToIndexedArray($data): array
    {
        // 确保数据是数组
        if (!is_array($data)) {
            throw new FileParseException('JSON data must be an array');
        }

        // 如果不是索引数组，转换为索引数组
        if (!isset($data[0])) {
            /** @var array<array<string, mixed>> */
            return [$data];
        }

        /** @var array<array<string, mixed>> */
        return $data;
    }

    /**
     * @param mixed $row
     */
    private function validateRowIsArray($row, int $index): void
    {
        if (!is_array($row)) {
            throw new FileParseException(sprintf('Row %d is not an array', $index));
        }
    }
}
