<?php

namespace AsyncImportBundle\Service;

use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Exception\FileParserNotFoundException;
use AsyncImportBundle\Exception\UnsupportedFileTypeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * 文件解析器工厂
 */
readonly class FileParserFactory
{
    /**
     * @param FileParserInterface[] $parsers
     */
    public function __construct(
        #[AutowireIterator(tag: 'async_import.file_parser')] private iterable $parsers,
    ) {
    }

    /**
     * 根据文件类型获取解析器
     */
    public function getParser(ImportFileType $fileType): FileParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($fileType)) {
                return $parser;
            }
        }

        throw new FileParserNotFoundException(sprintf('No parser found for file type: %s', $fileType->value));
    }

    /**
     * 根据文件扩展名猜测文件类型
     */
    public function guessFileType(string $filePath): ImportFileType
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => ImportFileType::CSV,
            'xls' => ImportFileType::XLS,
            'xlsx' => ImportFileType::XLSX,
            'json' => ImportFileType::JSON,
            default => throw new UnsupportedFileTypeException(sprintf('Unknown file extension: %s', $extension)),
        };
    }

    /**
     * 获取支持的文件类型
     *
     * @return array<ImportFileType>
     */
    public function getSupportedFileTypes(): array
    {
        $types = [];
        foreach ($this->parsers as $parser) {
            foreach (ImportFileType::cases() as $fileType) {
                if ($parser->supports($fileType)) {
                    $types[] = $fileType;
                }
            }
        }

        return array_unique($types, SORT_REGULAR);
    }
}
