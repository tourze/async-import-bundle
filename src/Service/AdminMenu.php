<?php

declare(strict_types=1);

namespace AsyncImportBundle\Service;

use AsyncImportBundle\Entity\AsyncImportErrorLog;
use AsyncImportBundle\Entity\AsyncImportTask;
use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

/**
 * 异步导入管理菜单服务
 */
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('异步导入')) {
            $item->addChild('异步导入');
        }

        $asyncImportMenu = $item->getChild('异步导入');
        if (null === $asyncImportMenu) {
            return;
        }

        // 导入任务管理菜单
        $asyncImportMenu->addChild('导入任务')
            ->setUri($this->linkGenerator->getCurdListPage(AsyncImportTask::class))
            ->setAttribute('icon', 'fas fa-tasks')
            ->setAttribute('description', '管理异步导入任务，查看导入进度和结果')
        ;

        // 错误日志管理菜单
        $asyncImportMenu->addChild('错误日志')
            ->setUri($this->linkGenerator->getCurdListPage(AsyncImportErrorLog::class))
            ->setAttribute('icon', 'fas fa-exclamation-triangle')
            ->setAttribute('description', '查看导入过程中的错误记录')
        ;
    }
}
