<?php

namespace AsyncImportBundle\Service;

use AsyncImportBundle\Enum\ImportFileType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * 文件解析器工厂
 */
class FileParserFactory
{
    /**
     * @var FileParserInterface[]
     */
    private iterable $parsers;

    public function __construct(
        #[AutowireIterator('async_import.file_parser')] iterable $parsers
    ) {
        $this->parsers = $parsers;
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

        throw new \RuntimeException(sprintf('No parser found for file type: %s', $fileType->value));
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
            default => throw new \RuntimeException(sprintf('Unknown file extension: %s', $extension))
        };
    }

    /**
     * 获取支持的文件类型
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