# AsyncImportBundle

Symfony异步导入模块

## 安装

```bash
composer require tourze/async-import-bundle
```

## 功能特性

- 为Symfony应用提供异步数据导入功能
- 导入任务管理
- 导入错误日志记录

## 配置

在`config/bundles.php`中注册此Bundle：

```php
return [
    // ...
    AsyncImportBundle\AsyncImportBundle::class => ['all' => true],
    // ...
];
```

## 使用方法

待补充

## 测试

本模块提供了完整的测试套件：

```bash
# 从项目根目录运行所有测试
./vendor/bin/phpunit packages/async-import-bundle/tests

# 运行特定的测试类
./vendor/bin/phpunit packages/async-import-bundle/tests/Entity/AsyncImportTaskTest.php
```

测试覆盖以下方面：
- 实体类测试：所有属性的getter/setter方法
- 仓库类测试：确保仓库类正确初始化
- 依赖注入测试：确保服务配置正确加载
- 集成测试：验证服务注册和容器配置

## 参考文档

- [Symfony Bundle开发文档](https://symfony.com/doc/current/bundles.html)
- [Doctrine ORM文档](https://www.doctrine-project.org/projects/doctrine-orm/en/2.14/index.html)
