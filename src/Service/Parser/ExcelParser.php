<?php

namespace AsyncImportBundle\Service\Parser;

use AsyncImportBundle\DTO\ValidationResult;
use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Exception\FileParseException;
use AsyncImportBundle\Service\FileParserInterface;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

/**
 * Excel 文件解析器
 */
class ExcelParser implements FileParserInterface
{
    public function supports(ImportFileType $fileType): bool
    {
        return in_array($fileType, [
            ImportFileType::EXCEL,
            ImportFileType::XLS,
            ImportFileType::XLSX,
        ], true);
    }

    public function parse(string $filePath, array $options = []): \Iterator
    {
        $this->validateFileExists($filePath);

        $options = $this->parseOptions($options);
        $spreadsheet = $this->loadSpreadsheet($filePath, $options['sheetIndex']);
        $worksheet = $spreadsheet->getSheet($options['sheetIndex']);

        $headers = null;
        $rowNumber = 0;

        foreach ($worksheet->getRowIterator() as $row) {
            ++$rowNumber;

            if ($this->shouldStopProcessing($rowNumber, $options['maxRows'])) {
                break;
            }

            $data = $this->extractRowData($row);

            if ($this->isEmptyRow($data)) {
                continue;
            }

            if ($this->shouldProcessAsHeader($options['skipHeader'], $headers)) {
                $headers = $data;
                continue;
            }

            yield $this->formatRowData($data, $headers);
        }

        $this->cleanupSpreadsheet($spreadsheet);
    }

    public function countRows(string $filePath, array $options = []): int
    {
        $parseOptions = $this->parseOptions($options);
        $sheetIndex = $parseOptions['sheetIndex'];
        $skipHeader = $parseOptions['skipHeader'];

        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheet($sheetIndex);

        $highestRow = $worksheet->getHighestRow();
        $count = $highestRow;

        // 减去表头行
        if ($skipHeader && $count > 0) {
            --$count;
        }

        // 清理内存
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $count;
    }

    public function getHeaders(string $filePath, array $options = []): array
    {
        $parseOptions = $this->parseOptions($options);
        $sheetIndex = $parseOptions['sheetIndex'];

        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheet($sheetIndex);

        $headers = [];
        $firstRow = $worksheet->getRowIterator(1, 1)->current();

        $cellIterator = $firstRow->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        foreach ($cellIterator as $cell) {
            $value = $cell->getCalculatedValue();
            if (null !== $value && '' !== $value && (is_string($value) || is_numeric($value))) {
                $headers[] = trim((string) $value);
            } else {
                // 如果遇到空列，停止读取
                break;
            }
        }

        // 清理内存
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $headers;
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
        if (!in_array($extension, ['xls', 'xlsx'], true)) {
            return ValidationResult::failure(sprintf('文件扩展名不正确: %s', $extension));
        }

        // 尝试加载文件验证格式
        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);

            // 尝试加载第一个工作表
            $reader->setLoadSheetsOnly(['Sheet1']);
            $spreadsheet = $reader->load($filePath);

            // 检查是否有工作表
            if (0 === $spreadsheet->getSheetCount()) {
                return ValidationResult::failure('Excel文件中没有工作表');
            }

            // 清理内存
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return ValidationResult::success();
        } catch (\Exception $e) {
            return ValidationResult::failure(sprintf('无法读取Excel文件: %s', $e->getMessage()));
        }
    }

    /**
     * 获取工作表列表
     * @return array<string>
     */
    public function getSheetNames(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);
        $sheetNames = $spreadsheet->getSheetNames();

        // 清理内存
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $sheetNames;
    }

    private function validateFileExists(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new FileParseException(sprintf('File not found: %s', $filePath));
        }
    }

    /**
     * @param array<string, mixed> $options
     * @return array{sheetIndex: int, skipHeader: bool, maxRows: int|null}
     */
    private function parseOptions(array $options): array
    {
        return [
            'sheetIndex' => is_numeric($options['sheetIndex'] ?? null) ? (int) $options['sheetIndex'] : 0,
            'skipHeader' => is_bool($options['skipHeader'] ?? null) ? $options['skipHeader'] : true,
            'maxRows' => is_numeric($options['maxRows'] ?? null) ? (int) $options['maxRows'] : null,
        ];
    }

    private function loadSpreadsheet(string $filePath, int $sheetIndex): Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        return $reader->load($filePath);
    }

    private function shouldStopProcessing(int $rowNumber, ?int $maxRows): bool
    {
        return null !== $maxRows && $rowNumber > $maxRows;
    }

    /**
     * @return array<mixed>
     */
    private function extractRowData(Row $row): array
    {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $data = [];
        foreach ($cellIterator as $cell) {
            $value = $cell->getCalculatedValue();
            $data[] = $this->formatCellValue($cell, $value);
        }

        return $data;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function formatCellValue(Cell $cell, $value)
    {
        // 处理日期格式
        if ('d' === $cell->getDataType()
            || Date::isDateTime($cell)) {
            try {
                if (is_numeric($value)) {
                    $dateValue = Date::excelToDateTimeObject((float) $value);
                    $value = $dateValue->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                // 保持原值
            }
        }

        return $value;
    }

    /**
     * @param array<mixed> $data
     */
    private function isEmptyRow(array $data): bool
    {
        return [] === array_filter($data, function ($v) {
            return null !== $v && '' !== $v;
        });
    }

    /**
     * @param array<mixed>|null $headers
     */
    private function shouldProcessAsHeader(bool $skipHeader, ?array $headers): bool
    {
        return $skipHeader && null === $headers;
    }

    /**
     * @param array<mixed> $data
     * @param array<mixed>|null $headers
     * @return array<string, mixed>|array<mixed>
     */
    private function formatRowData(array $data, ?array $headers): array
    {
        if (null !== $headers) {
            $dataCount = count($data);
            $headerCount = count($headers);

            if ($dataCount < $headerCount) {
                $data = array_pad($data, $headerCount, '');
            } elseif ($dataCount > $headerCount) {
                $data = array_slice($data, 0, $headerCount);
            }

            /** @var array<string> $cleanHeaders */
            $cleanHeaders = array_map(fn ($h) => is_scalar($h) ? (string) $h : '', $headers);

            return array_combine($cleanHeaders, $data);
        }

        return $data;
    }

    private function cleanupSpreadsheet(Spreadsheet $spreadsheet): void
    {
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }
}
