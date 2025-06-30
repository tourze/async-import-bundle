<?php

namespace AsyncImportBundle\Service\Parser;

use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Exception\FileParseException;
use AsyncImportBundle\Service\FileParserInterface;
use AsyncImportBundle\Service\ValidationResult;

/**
 * JSON 文件解析器
 */
class JsonParser implements FileParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(ImportFileType $fileType): bool
    {
        return $fileType === ImportFileType::JSON;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $filePath, array $options = []): \Iterator
    {
        $rootKey = $options['rootKey'] ?? null;
        $skipHeader = $options['skipHeader'] ?? false; // JSON 通常不需要跳过表头
        
        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileParseException(sprintf('Failed to read file: %s', $filePath));
        }
        
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new FileParseException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }
        
        // 如果指定了根键，获取该键的数据
        if ($rootKey !== null && isset($data[$rootKey])) {
            $data = $data[$rootKey];
        }
        
        // 确保数据是数组
        if (!is_array($data)) {
            throw new FileParseException('JSON data must be an array');
        }
        
        // 如果不是索引数组，转换为索引数组
        if (!isset($data[0])) {
            $data = [$data];
        }
        
        foreach ($data as $index => $row) {
            if (!is_array($row)) {
                throw new FileParseException(sprintf('Row %d is not an array', $index));
            }
            
            yield $row;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countRows(string $filePath, array $options = []): int
    {
        $rootKey = $options['rootKey'] ?? null;
        
        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileParseException(sprintf('Failed to read file: %s', $filePath));
        }
        
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new FileParseException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }
        
        // 如果指定了根键，获取该键的数据
        if ($rootKey !== null && isset($data[$rootKey])) {
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
     * {@inheritdoc}
     */
    public function getHeaders(string $filePath, array $options = []): array
    {
        $rootKey = $options['rootKey'] ?? null;
        
        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileParseException(sprintf('Failed to read file: %s', $filePath));
        }
        
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new FileParseException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }
        
        // 如果指定了根键，获取该键的数据
        if ($rootKey !== null && isset($data[$rootKey])) {
            $data = $data[$rootKey];
        }
        
        if (!is_array($data)) {
            return [];
        }
        
        // 获取第一个元素的键作为表头
        $firstRow = isset($data[0]) ? $data[0] : $data;
        
        if (!is_array($firstRow)) {
            return [];
        }
        
        return array_keys($firstRow);
    }

    /**
     * {@inheritdoc}
     */
    public function validateFormat(string $filePath): ValidationResult
    {
        if (!file_exists($filePath)) {
            return ValidationResult::failure('文件不存在');
        }
        
        if (!is_readable($filePath)) {
            return ValidationResult::failure('文件不可读');
        }
        
        $fileSize = filesize($filePath);
        if ($fileSize === 0) {
            return ValidationResult::failure('文件为空');
        }
        
        // 检查文件扩展名
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'json') {
            return ValidationResult::failure(sprintf('文件扩展名不正确: %s', $extension));
        }
        
        // 尝试解析JSON验证格式
        $content = file_get_contents($filePath);
        if ($content === false) {
            return ValidationResult::failure('无法读取文件内容');
        }
        
        json_decode($content);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ValidationResult::failure(sprintf('无效的JSON格式: %s', json_last_error_msg()));
        }
        
        return ValidationResult::success();
    }
}