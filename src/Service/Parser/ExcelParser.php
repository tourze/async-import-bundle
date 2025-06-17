<?php

namespace AsyncImportBundle\Service\Parser;

use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Service\FileParserInterface;
use AsyncImportBundle\Service\ValidationResult;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Excel 文件解析器
 */
class ExcelParser implements FileParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(ImportFileType $fileType): bool
    {
        return in_array($fileType, [
            ImportFileType::EXCEL,
            ImportFileType::XLS,
            ImportFileType::XLSX
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $filePath, array $options = []): \Iterator
    {
        $sheetIndex = $options['sheetIndex'] ?? 0;
        $skipHeader = $options['skipHeader'] ?? true;
        $maxRows = $options['maxRows'] ?? null;
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException(sprintf('File not found: %s', $filePath));
        }
        
        // 使用 IOFactory 自动检测文件类型
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheet($sheetIndex);
        
        $headers = null;
        $rowNumber = 0;
        
        foreach ($worksheet->getRowIterator() as $row) {
            $rowNumber++;
            
            if ($maxRows !== null && $rowNumber > $maxRows) {
                break;
            }
            
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            $data = [];
            foreach ($cellIterator as $cell) {
                $value = $cell->getCalculatedValue();
                
                // 处理日期格式
                if ($cell->getDataType() === 'd' || 
                    \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                    try {
                        $value = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                        $value = $value->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        // 保持原值
                    }
                }
                
                $data[] = $value;
            }
            
            // 跳过空行
            if (empty(array_filter($data, function($v) { return $v !== null && $v !== ''; }))) {
                continue;
            }
            
            // 处理表头
            if ($skipHeader && $headers === null) {
                $headers = $data;
                continue;
            }
            
            // 如果有表头，转换为关联数组
            if ($headers !== null) {
                $dataCount = count($data);
                $headerCount = count($headers);
                
                if ($dataCount < $headerCount) {
                    $data = array_pad($data, $headerCount, '');
                } elseif ($dataCount > $headerCount) {
                    $data = array_slice($data, 0, $headerCount);
                }
                
                yield array_combine($headers, $data);
            } else {
                yield $data;
            }
        }
        
        // 清理内存
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    /**
     * {@inheritdoc}
     */
    public function countRows(string $filePath, array $options = []): int
    {
        $sheetIndex = $options['sheetIndex'] ?? 0;
        $skipHeader = $options['skipHeader'] ?? true;
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException(sprintf('File not found: %s', $filePath));
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
            $count--;
        }
        
        // 清理内存
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(string $filePath, array $options = []): array
    {
        $sheetIndex = $options['sheetIndex'] ?? 0;
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException(sprintf('File not found: %s', $filePath));
        }
        
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheet($sheetIndex);
        
        $headers = [];
        $firstRow = $worksheet->getRowIterator(1, 1)->current();
        
        if ($firstRow) {
            $cellIterator = $firstRow->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            foreach ($cellIterator as $cell) {
                $value = $cell->getCalculatedValue();
                if ($value !== null && $value !== '') {
                    $headers[] = trim((string) $value);
                } else {
                    // 如果遇到空列，停止读取
                    break;
                }
            }
        }
        
        // 清理内存
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
        return $headers;
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
        if (!in_array($extension, ['xls', 'xlsx'])) {
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
            if ($spreadsheet->getSheetCount() === 0) {
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
     */
    public function getSheetNames(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException(sprintf('File not found: %s', $filePath));
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
}