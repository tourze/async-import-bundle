<?php

namespace AsyncImportBundle\Service;

use AsyncImportBundle\Exception\ImportHandlerNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * 导入处理器注册表
 */
readonly class ImportHandlerRegistry
{
    /**
     * @param ImportHandlerInterface[] $handlers
     */
    public function __construct(
        #[AutowireIterator(tag: 'async_import.handler')] private iterable $handlers,
    ) {
    }

    /**
     * 根据实体类名获取处理器
     */
    public function getHandler(string $entityClass): ImportHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($entityClass)) {
                return $handler;
            }
        }

        throw new ImportHandlerNotFoundException(sprintf('No import handler found for entity class: %s', $entityClass));
    }

    /**
     * 检查是否有处理器支持指定的实体类
     */
    public function hasHandler(string $entityClass): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($entityClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取所有支持的实体类
     *
     * @return array<string>
     */
    public function getSupportedEntityClasses(): array
    {
        $classes = [];
        foreach ($this->handlers as $handler) {
            $classes[] = $handler->getEntityClass();
        }

        return array_unique($classes);
    }

    /**
     * 获取所有处理器
     *
     * @return array<ImportHandlerInterface>
     */
    public function getAllHandlers(): array
    {
        return iterator_to_array($this->handlers);
    }
}
