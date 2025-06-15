<?php

namespace AsyncImportBundle\Service;

use AsyncImportBundle\Entity\AsyncImportTask;

/**
 * 导入处理器接口
 * 各项目需要实现此接口来处理具体的导入逻辑
 */
interface ImportHandlerInterface
{
    /**
     * 判断是否支持处理指定的实体类
     */
    public function supports(string $entityClass): bool;

    /**
     * 验证单行数据
     * 
     * @param array $row 原始数据行
     * @param int $lineNumber 行号
     * @return ValidationResult 验证结果
     */
    public function validate(array $row, int $lineNumber): ValidationResult;

    /**
     * 导入单行数据
     * 
     * @param array $row 已验证的数据行
     * @param AsyncImportTask $task 导入任务
     * @throws \Exception 导入失败时抛出异常
     */
    public function import(array $row, AsyncImportTask $task): void;

    /**
     * 获取字段映射配置
     * 返回格式: ['csv列名' => '实体属性名']
     */
    public function getFieldMapping(): array;

    /**
     * 获取批次大小
     * 用于控制每批处理的数据量
     */
    public function getBatchSize(): int;

    /**
     * 获取实体类名
     */
    public function getEntityClass(): string;

    /**
     * 预处理数据行
     * 在验证前对数据进行转换或清理
     */
    public function preprocess(array $row): array;
}