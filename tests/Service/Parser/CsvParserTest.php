<?php

namespace AsyncImportBundle\Tests\Service\Parser;

use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Service\Parser\CsvParser;
use PHPUnit\Framework\TestCase;

class CsvParserTest extends TestCase
{
    private CsvParser $parser;
    private string $tempDir;
    
    protected function setUp(): void
    {
        $this->parser = new CsvParser();
        $this->tempDir = sys_get_temp_dir() . '/csv_parser_test_' . uniqid();
        mkdir($this->tempDir);
    }
    
    protected function tearDown(): void
    {
        // 清理临时文件
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);
    }
    
    public function testSupports(): void
    {
        $this->assertTrue($this->parser->supports(ImportFileType::CSV));
        $this->assertFalse($this->parser->supports(ImportFileType::XLSX));
        $this->assertFalse($this->parser->supports(ImportFileType::JSON));
    }
    
    public function testParseWithHeaders(): void
    {
        $csvFile = $this->createCsvFile([
            ['id', 'name', 'email'],
            ['1', 'John Doe', 'john@example.com'],
            ['2', 'Jane Smith', 'jane@example.com'],
        ]);
        
        $rows = iterator_to_array($this->parser->parse($csvFile));
        
        $this->assertCount(2, $rows);
        $this->assertSame(['id' => '1', 'name' => 'John Doe', 'email' => 'john@example.com'], $rows[0]);
        $this->assertSame(['id' => '2', 'name' => 'Jane Smith', 'email' => 'jane@example.com'], $rows[1]);
    }
    
    public function testParseWithoutHeaders(): void
    {
        $csvFile = $this->createCsvFile([
            ['1', 'John Doe', 'john@example.com'],
            ['2', 'Jane Smith', 'jane@example.com'],
        ]);
        
        $rows = iterator_to_array($this->parser->parse($csvFile, ['skipHeader' => false]));
        
        $this->assertCount(2, $rows);
        $this->assertSame(['1', 'John Doe', 'john@example.com'], $rows[0]);
        $this->assertSame(['2', 'Jane Smith', 'jane@example.com'], $rows[1]);
    }
    
    public function testCountRows(): void
    {
        $csvFile = $this->createCsvFile([
            ['id', 'name', 'email'],
            ['1', 'John Doe', 'john@example.com'],
            ['2', 'Jane Smith', 'jane@example.com'],
            ['3', 'Bob Johnson', 'bob@example.com'],
        ]);
        
        $count = $this->parser->countRows($csvFile);
        $this->assertSame(3, $count);
        
        $countWithHeader = $this->parser->countRows($csvFile, ['skipHeader' => false]);
        $this->assertSame(4, $countWithHeader);
    }
    
    public function testGetHeaders(): void
    {
        $csvFile = $this->createCsvFile([
            ['id', 'name', 'email'],
            ['1', 'John Doe', 'john@example.com'],
        ]);
        
        $headers = $this->parser->getHeaders($csvFile);
        
        $this->assertSame(['id', 'name', 'email'], $headers);
    }
    
    public function testValidateFormat(): void
    {
        $csvFile = $this->createCsvFile([
            ['id', 'name'],
            ['1', 'Test'],
        ]);
        
        $result = $this->parser->validateFormat($csvFile);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }
    
    public function testValidateFormatFileNotExists(): void
    {
        $result = $this->parser->validateFormat('/nonexistent/file.csv');
        
        $this->assertFalse($result->isValid());
        $this->assertSame('文件不存在', $result->getErrorMessage());
    }
    
    private function createCsvFile(array $data): string
    {
        $filename = $this->tempDir . '/test_' . uniqid() . '.csv';
        $handle = fopen($filename, 'w');
        
        foreach ($data as $row) {
            fputcsv($handle, $row, ',', '"', '\\');
        }
        
        fclose($handle);
        return $filename;
    }
}