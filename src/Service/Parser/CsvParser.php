<?php

namespace AsyncImportBundle\Service\Parser;

use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Service\FileParserInterface;
use AsyncImportBundle\Service\ValidationResult;

/**
 * CSV 文件解析器
 */
class CsvParser implements FileParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(ImportFileType $fileType): bool
    {
        return $fileType === ImportFileType::CSV;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $filePath, array $options = []): \Iterator
    {
        $delimiter = $options['delimiter'] ?? ',';
        $enclosure = $options['enclosure'] ?? '"';
        $escape = $options['escape'] ?? '\\';
        $skipHeader = $options['skipHeader'] ?? true;
        $encoding = $options['encoding'] ?? 'UTF-8';
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException(sprintf('File not found: %s', $filePath));
        }
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException(sprintf('Failed to open file: %s', $filePath));
        }
        
        // 检测并转换编码
        if ($encoding !== 'UTF-8') {
            stream_filter_append($handle, sprintf('convert.iconv.%s/UTF-8', $encoding));
        }
        
        try {
            $headers = null;
            $lineNumber = 0;
            
            while (($data = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
                $lineNumber++;
                
                // 跳过空行
                if (empty($data) || (count($data) === 1 && empty($data[0]))) {
                    continue;
                }
                
                // 处理表头
                if ($skipHeader && $headers === null) {
                    $headers = $data;
                    continue;
                }
                
                // 如果有表头，转换为关联数组
                if ($headers !== null) {
                    // 确保数据列数与表头一致
                    $dataCount = count($data);
                    $headerCount = count($headers);
                    
                    if ($dataCount < $headerCount) {
                        // 补充缺少的列
                        $data = array_pad($data, $headerCount, '');
                    } elseif ($dataCount > $headerCount) {
                        // 截断多余的列
                        $data = array_slice($data, 0, $headerCount);
                    }
                    
                    yield array_combine($headers, $data);
                } else {
                    yield $data;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countRows(string $filePath, array $options = []): int
    {
        $skipHeader = $options['skipHeader'] ?? true;
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException(sprintf('File not found: %s', $filePath));
        }
        
        $lineCount = 0;
        $handle = fopen($filePath, 'r');
        
        if (!$handle) {
            throw new \RuntimeException(sprintf('Failed to open file: %s', $filePath));
        }
        
        try {
            while (fgets($handle) !== false) {
                $lineCount++;
            }
            
            // 减去表头行
            if ($skipHeader && $lineCount > 0) {
                $lineCount--;
            }
            
            return $lineCount;
        } finally {
            fclose($handle);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(string $filePath, array $options = []): array
    {
        $delimiter = $options['delimiter'] ?? ',';
        $enclosure = $options['enclosure'] ?? '"';
        $escape = $options['escape'] ?? '\\';
        $encoding = $options['encoding'] ?? 'UTF-8';
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException(sprintf('File not found: %s', $filePath));
        }
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException(sprintf('Failed to open file: %s', $filePath));
        }
        
        // 检测并转换编码
        if ($encoding !== 'UTF-8') {
            stream_filter_append($handle, sprintf('convert.iconv.%s/UTF-8', $encoding));
        }
        
        try {
            $headers = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
            
            if ($headers === false) {
                return [];
            }
            
            // 清理表头（去除空格、BOM等）
            return array_map(function ($header) {
                // 移除 BOM
                $header = str_replace("\xEF\xBB\xBF", '', $header);
                // 去除首尾空格
                return trim($header);
            }, $headers);
            
        } finally {
            fclose($handle);
        }
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
        if ($extension !== 'csv') {
            return ValidationResult::failure(sprintf('文件扩展名不正确: %s', $extension));
        }
        
        // 尝试读取第一行验证格式
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ValidationResult::failure('无法打开文件');
        }
        
        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return ValidationResult::failure('无法读取文件内容');
            }
            
            // 检查是否包含分隔符
            if (strpos($firstLine, ',') === false && 
                strpos($firstLine, ';') === false && 
                strpos($firstLine, "\t") === false) {
                return ValidationResult::failure('未找到有效的CSV分隔符');
            }
            
            return ValidationResult::success();
            
        } finally {
            fclose($handle);
        }
    }
}