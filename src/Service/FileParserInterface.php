<?php

namespace AsyncImportBundle\Service;

use AsyncImportBundle\Enum\ImportFileType;

/**
 * 文件解析器接口
 */
interface FileParserInterface
{
    /**
     * 判断是否支持指定的文件类型
     */
    public function supports(ImportFileType $fileType): bool;

    /**
     * 解析文件并返回数据行迭代器
     *
     * @param string $filePath 文件路径
     * @param array $options 解析选项
     * @return \Iterator 数据行迭代器
     */
    public function parse(string $filePath, array $options = []): \Iterator;

    /**
     * 获取文件总行数（不包括表头）
     */
    public function countRows(string $filePath, array $options = []): int;

    /**
     * 获取文件的列标题
     */
    public function getHeaders(string $filePath, array $options = []): array;

    /**
     * 验证文件格式是否正确
     */
    public function validateFormat(string $filePath): ValidationResult;
}