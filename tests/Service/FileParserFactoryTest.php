<?php

namespace AsyncImportBundle\Tests\Service;

use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Service\FileParserFactory;
use AsyncImportBundle\Service\FileParserInterface;
use PHPUnit\Framework\TestCase;

class FileParserFactoryTest extends TestCase
{
    public function testGetParser(): void
    {
        $csvParser = $this->createMock(FileParserInterface::class);
        $csvParser->method('supports')->willReturnCallback(
            fn($type) => $type === ImportFileType::CSV
        );
        
        $excelParser = $this->createMock(FileParserInterface::class);
        $excelParser->method('supports')->willReturnCallback(
            fn($type) => in_array($type, [ImportFileType::XLS, ImportFileType::XLSX], true)
        );
        
        $factory = new FileParserFactory([$csvParser, $excelParser]);
        
        $this->assertSame($csvParser, $factory->getParser(ImportFileType::CSV));
        $this->assertSame($excelParser, $factory->getParser(ImportFileType::XLSX));
    }
    
    public function testGetParserNotFound(): void
    {
        $csvParser = $this->createMock(FileParserInterface::class);
        $csvParser->method('supports')->willReturn(false);
        
        $factory = new FileParserFactory([$csvParser]);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No parser found for file type: json');
        
        $factory->getParser(ImportFileType::JSON);
    }
    
    public function testGuessFileType(): void
    {
        $factory = new FileParserFactory([]);
        
        $this->assertSame(ImportFileType::CSV, $factory->guessFileType('/path/to/file.csv'));
        $this->assertSame(ImportFileType::CSV, $factory->guessFileType('/path/to/file.CSV'));
        $this->assertSame(ImportFileType::XLS, $factory->guessFileType('/path/to/file.xls'));
        $this->assertSame(ImportFileType::XLSX, $factory->guessFileType('/path/to/file.xlsx'));
        $this->assertSame(ImportFileType::JSON, $factory->guessFileType('/path/to/file.json'));
    }
    
    public function testGuessFileTypeUnknownExtension(): void
    {
        $factory = new FileParserFactory([]);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown file extension: txt');
        
        $factory->guessFileType('/path/to/file.txt');
    }
    
    public function testGetSupportedFileTypes(): void
    {
        $csvParser = $this->createMock(FileParserInterface::class);
        $csvParser->method('supports')->willReturnCallback(
            fn($type) => $type === ImportFileType::CSV
        );
        
        $excelParser = $this->createMock(FileParserInterface::class);
        $excelParser->method('supports')->willReturnCallback(
            fn($type) => in_array($type, [ImportFileType::XLS, ImportFileType::XLSX], true)
        );
        
        $factory = new FileParserFactory([$csvParser, $excelParser]);
        
        $supportedTypes = $factory->getSupportedFileTypes();
        
        $this->assertContains(ImportFileType::CSV, $supportedTypes);
        $this->assertContains(ImportFileType::XLS, $supportedTypes);
        $this->assertContains(ImportFileType::XLSX, $supportedTypes);
        $this->assertNotContains(ImportFileType::JSON, $supportedTypes);
    }
}