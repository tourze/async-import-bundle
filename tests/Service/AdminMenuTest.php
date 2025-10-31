<?php

declare(strict_types=1);

namespace AsyncImportBundle\Tests\Service;

use AsyncImportBundle\Service\AdminMenu;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // Required implementation for AbstractEasyAdminMenuTestCase
    }

    public function testCanBeInstantiated(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
    }

    public function testInvokeMethodCallsCorrectMethods(): void
    {
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $linkGenerator->expects($this->exactly(2))
            ->method('getCurdListPage')
            ->willReturn('/admin/test')
        ;

        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);
        $adminMenu = self::getService(AdminMenu::class);

        // 创建一个简化的 mock 菜单项
        $childItem = $this->createMock(ItemInterface::class);
        $childItem->method('setUri')->willReturnSelf();
        $childItem->method('setAttribute')->willReturnSelf();
        $childItem->method('addChild')->willReturnSelf();

        $menuItem = $this->createMock(ItemInterface::class);
        $menuItem->expects($this->exactly(2))
            ->method('getChild')
            ->with('异步导入')
            ->willReturnOnConsecutiveCalls(null, $childItem)
        ;

        $menuItem->expects($this->once())
            ->method('addChild')
            ->with('异步导入')
            ->willReturn($childItem)
        ;

        $childItem->expects($this->exactly(2))
            ->method('addChild')
            ->with(self::callback(static function (string $value): bool {
                return \in_array($value, ['导入任务', '错误日志'], true);
            }))
            ->willReturnSelf()
        ;

        // 调用测试方法
        $adminMenu($menuItem);

        // 测试成功完成意味着所有预期的方法调用都发生了
        $this->assertTrue(true);
    }
}
