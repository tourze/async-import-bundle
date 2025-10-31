<?php

namespace AsyncImportBundle\Tests\Service;

use AsyncImportBundle\DTO\ValidationResult;
use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Service\FileParserFactory;
use AsyncImportBundle\Service\FileParserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FileParserFactory::class)]
final class FileParserFactoryTest extends TestCase
{
    public function testGetParser(): void
    {
        $csvParser = new class implements FileParserInterface {
            public function supports(ImportFileType $fileType): bool
            {
                return ImportFileType::CSV === $fileType;
            }

            public function parse(string $filePath, array $options = []): \Iterator
            {
                return new \EmptyIterator();
            }

            public function countRows(string $filePath, array $options = []): int
            {
                return 0;
            }

            public function getHeaders(string $filePath, array $options = []): array
            {
                return [];
            }

            public function validateFormat(string $filePath): ValidationResult
            {
                return new ValidationResult(true);
            }
        };

        $excelParser = new class implements FileParserInterface {
            public function supports(ImportFileType $fileType): bool
            {
                return in_array($fileType, [ImportFileType::XLS, ImportFileType::XLSX], true);
            }

            public function parse(string $filePath, array $options = []): \Iterator
            {
                return new \EmptyIterator();
            }

            public function countRows(string $filePath, array $options = []): int
            {
                return 0;
            }

            public function getHeaders(string $filePath, array $options = []): array
            {
                return [];
            }

            public function validateFormat(string $filePath): ValidationResult
            {
                return new ValidationResult(true);
            }
        };

        $factory = new FileParserFactory([$csvParser, $excelParser]);

        $this->assertSame($csvParser, $factory->getParser(ImportFileType::CSV));
        $this->assertSame($excelParser, $factory->getParser(ImportFileType::XLSX));
    }

    public function testGetParserNotFound(): void
    {
        $csvParser = new class implements FileParserInterface {
            public function supports(ImportFileType $fileType): bool
            {
                return false;
            }

            public function parse(string $filePath, array $options = []): \Iterator
            {
                return new \EmptyIterator();
            }

            public function countRows(string $filePath, array $options = []): int
            {
                return 0;
            }

            public function getHeaders(string $filePath, array $options = []): array
            {
                return [];
            }

            public function validateFormat(string $filePath): ValidationResult
            {
                return new ValidationResult(true);
            }
        };

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
        $csvParser = new class implements FileParserInterface {
            public function supports(ImportFileType $fileType): bool
            {
                return ImportFileType::CSV === $fileType;
            }

            public function parse(string $filePath, array $options = []): \Iterator
            {
                return new \EmptyIterator();
            }

            public function countRows(string $filePath, array $options = []): int
            {
                return 0;
            }

            public function getHeaders(string $filePath, array $options = []): array
            {
                return [];
            }

            public function validateFormat(string $filePath): ValidationResult
            {
                return new ValidationResult(true);
            }
        };

        $excelParser = new class implements FileParserInterface {
            public function supports(ImportFileType $fileType): bool
            {
                return in_array($fileType, [ImportFileType::XLS, ImportFileType::XLSX], true);
            }

            public function parse(string $filePath, array $options = []): \Iterator
            {
                return new \EmptyIterator();
            }

            public function countRows(string $filePath, array $options = []): int
            {
                return 0;
            }

            public function getHeaders(string $filePath, array $options = []): array
            {
                return [];
            }

            public function validateFormat(string $filePath): ValidationResult
            {
                return new ValidationResult(true);
            }
        };

        $factory = new FileParserFactory([$csvParser, $excelParser]);

        $supportedTypes = $factory->getSupportedFileTypes();

        $this->assertContains(ImportFileType::CSV, $supportedTypes);
        $this->assertContains(ImportFileType::XLS, $supportedTypes);
        $this->assertContains(ImportFileType::XLSX, $supportedTypes);
        $this->assertNotContains(ImportFileType::JSON, $supportedTypes);
    }
}
