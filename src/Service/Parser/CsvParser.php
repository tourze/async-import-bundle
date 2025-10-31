<?php

namespace AsyncImportBundle\Service\Parser;

use AsyncImportBundle\DTO\ValidationResult;
use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Exception\FileParseException;
use AsyncImportBundle\Service\FileParserInterface;

/**
 * CSV 文件解析器
 */
class CsvParser implements FileParserInterface
{
    public function supports(ImportFileType $fileType): bool
    {
        return ImportFileType::CSV === $fileType;
    }

    public function parse(string $filePath, array $options = []): \Iterator
    {
        $this->validateFileExists($filePath);

        $parseOptions = $this->parseCsvOptions($options);
        $handle = $this->openFileHandle($filePath, $parseOptions['encoding']);

        try {
            $headers = null;
            $lineNumber = 0;

            while (($data = $this->readCsvLine($handle, $parseOptions)) !== false) {
                ++$lineNumber;

                if ($this->isEmptyLine($data)) {
                    continue;
                }

                if ($this->shouldProcessAsHeader($parseOptions['skipHeader'], $headers)) {
                    $headers = $data;
                    continue;
                }

                yield $this->formatLineData($data, $headers);
            }
        } finally {
            fclose($handle);
        }
    }

    public function countRows(string $filePath, array $options = []): int
    {
        $skipHeader = (bool) ($options['skipHeader'] ?? true);

        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }

        $lineCount = 0;
        $handle = fopen($filePath, 'r');

        if (false === $handle) {
            throw new FileParseException(sprintf('Failed to open file: %s', $filePath));
        }

        try {
            while (false !== fgets($handle)) {
                ++$lineCount;
            }

            // 减去表头行
            if ($skipHeader && $lineCount > 0) {
                --$lineCount;
            }

            return $lineCount;
        } finally {
            fclose($handle);
        }
    }

    public function getHeaders(string $filePath, array $options = []): array
    {
        $parseOptions = $this->parseCsvOptions($options);
        $delimiter = $parseOptions['delimiter'];
        $enclosure = $parseOptions['enclosure'];
        $escape = $parseOptions['escape'];
        $encoding = $parseOptions['encoding'];

        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }

        $handle = fopen($filePath, 'r');
        if (false === $handle) {
            throw new FileParseException(sprintf('Failed to open file: %s', $filePath));
        }

        // 检测并转换编码
        if ('UTF-8' !== $encoding) {
            stream_filter_append($handle, sprintf('convert.iconv.%s/UTF-8', $encoding));
        }

        try {
            $headers = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);

            if (false === $headers) {
                return [];
            }

            // 清理表头（去除空格、BOM等）
            return array_map(fn ($header) => trim(str_replace("\xEF\xBB\xBF", '', $header ?? '')), $headers);
        } finally {
            fclose($handle);
        }
    }

    public function validateFormat(string $filePath): ValidationResult
    {
        if (!file_exists($filePath)) {
            return ValidationResult::failure('文件不存在');
        }

        if (!is_readable($filePath)) {
            return ValidationResult::failure('文件不可读');
        }

        if ($this->isFileEmpty($filePath)) {
            return ValidationResult::failure('文件为空');
        }

        if (!$this->hasValidExtension($filePath)) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            return ValidationResult::failure(sprintf('文件扩展名不正确: %s', $extension));
        }

        return $this->validateCsvContent($filePath);
    }

    private function validateFileExists(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }
    }

    /**
     * @param array<string, mixed> $options
     * @return array{delimiter: string, enclosure: string, escape: string, skipHeader: bool, encoding: string}
     */
    private function parseCsvOptions(array $options): array
    {
        return [
            'delimiter' => is_string($options['delimiter'] ?? null) ? $options['delimiter'] : ',',
            'enclosure' => is_string($options['enclosure'] ?? null) ? $options['enclosure'] : '"',
            'escape' => is_string($options['escape'] ?? null) ? $options['escape'] : '\\',
            'skipHeader' => is_bool($options['skipHeader'] ?? null) ? $options['skipHeader'] : true,
            'encoding' => is_string($options['encoding'] ?? null) ? $options['encoding'] : 'UTF-8',
        ];
    }

    /**
     * @return resource
     */
    private function openFileHandle(string $filePath, string $encoding)
    {
        $handle = fopen($filePath, 'r');
        if (false === $handle) {
            throw new FileParseException(sprintf('Failed to open file: %s', $filePath));
        }

        // 检测并转换编码
        if ('UTF-8' !== $encoding) {
            stream_filter_append($handle, sprintf('convert.iconv.%s/UTF-8', $encoding));
        }

        return $handle;
    }

    /**
     * @param resource $handle
     * @param array{delimiter: string, enclosure: string, escape: string, skipHeader: bool, encoding: string} $options
     * @return array<string|null>|false
     */
    private function readCsvLine($handle, array $options)
    {
        return fgetcsv(
            $handle,
            0,
            $options['delimiter'],
            $options['enclosure'],
            $options['escape']
        );
    }

    /**
     * @param array<string|null> $data
     */
    private function isEmptyLine(array $data): bool
    {
        return [] === $data || (1 === count($data) && (null === $data[0] || '' === $data[0]));
    }

    /**
     * @param array<string|null>|null $headers
     */
    private function shouldProcessAsHeader(bool $skipHeader, ?array $headers): bool
    {
        return $skipHeader && null === $headers;
    }

    /**
     * @param array<string|null> $data
     * @param array<string|null>|null $headers
     * @return array<string, string>|array<string>
     */
    private function formatLineData(array $data, ?array $headers): array
    {
        if (null !== $headers) {
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

            // 转换null值为空字符串，确保类型一致
            $cleanHeaders = array_map(fn ($h) => (string) $h, $headers);
            $cleanData = array_map(fn ($d) => (string) $d, $data);

            return array_combine($cleanHeaders, $cleanData);
        }

        // 转换null值为空字符串
        return array_map(fn ($d) => (string) $d, $data);
    }

    private function isFileEmpty(string $filePath): bool
    {
        return 0 === filesize($filePath);
    }

    private function hasValidExtension(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return 'csv' === $extension;
    }

    private function validateCsvContent(string $filePath): ValidationResult
    {
        $handle = fopen($filePath, 'r');
        if (false === $handle) {
            return ValidationResult::failure('无法打开文件');
        }

        try {
            $firstLine = fgets($handle);
            if (false === $firstLine) {
                return ValidationResult::failure('无法读取文件内容');
            }

            // 检查是否包含分隔符
            if (!$this->hasValidSeparator($firstLine)) {
                return ValidationResult::failure('未找到有效的CSV分隔符');
            }

            return ValidationResult::success();
        } finally {
            fclose($handle);
        }
    }

    private function hasValidSeparator(string $line): bool
    {
        return false !== strpos($line, ',')
            || false !== strpos($line, ';')
            || false !== strpos($line, "\t");
    }
}
