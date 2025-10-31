<?php

namespace AsyncImportBundle\Tests\Service\Parser;

use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Service\Parser\ExcelParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ExcelParser::class)]
final class ExcelParserTest extends TestCase
{
    private ExcelParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new ExcelParser();
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->parser->supports(ImportFileType::EXCEL));
        $this->assertTrue($this->parser->supports(ImportFileType::XLS));
        $this->assertTrue($this->parser->supports(ImportFileType::XLSX));
        $this->assertFalse($this->parser->supports(ImportFileType::CSV));
        $this->assertFalse($this->parser->supports(ImportFileType::JSON));
    }

    public function testParseFileNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found: /nonexistent/file.xlsx');

        iterator_to_array($this->parser->parse('/nonexistent/file.xlsx'));
    }

    public function testValidateFormat(): void
    {
        // 测试不存在的文件
        $result = $this->parser->validateFormat('/nonexistent/file.xlsx');

        $this->assertFalse($result->isValid());
        $this->assertSame('文件不存在', $result->getErrorMessage());
    }

    public function testValidateFormatEmptyFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.xlsx';
        touch($tempFile);

        try {
            $result = $this->parser->validateFormat($tempFile);

            $this->assertFalse($result->isValid());
            $this->assertSame('文件为空', $result->getErrorMessage());
        } finally {
            unlink($tempFile);
        }
    }

    public function testValidateFormatWrongExtension(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.txt';
        file_put_contents($tempFile, 'test content');

        try {
            $result = $this->parser->validateFormat($tempFile);

            $this->assertFalse($result->isValid());
            $this->assertSame('文件扩展名不正确: txt', $result->getErrorMessage());
        } finally {
            unlink($tempFile);
        }
    }

    public function testCountRows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found: /nonexistent/file.xlsx');

        $this->parser->countRows('/nonexistent/file.xlsx');
    }
}
