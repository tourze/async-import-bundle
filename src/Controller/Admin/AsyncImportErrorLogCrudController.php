<?php

declare(strict_types=1);

namespace AsyncImportBundle\Controller\Admin;

use AsyncImportBundle\Entity\AsyncImportErrorLog;
use AsyncImportBundle\Entity\AsyncImportTask;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * 异步导入错误日志管理控制器
 */
#[AdminCrud(routePath: '/async-import/error-log', routeName: 'async_import_error_log')]
#[Autoconfigure(public: true)]
final class AsyncImportErrorLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AsyncImportErrorLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('导入错误日志')
            ->setEntityLabelInPlural('导入错误日志')
            ->setPageTitle('index', '导入错误日志管理')
            ->setPageTitle('detail', '错误日志详情')
            ->setHelp('index', '查看导入过程中发生的错误详情，帮助分析和解决数据导入问题')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['taskId', 'entityClass', 'error'])
            ->setPaginatorPageSize(30)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // ID 字段
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->onlyOnIndex()
        ;

        // 基本信息字段
        // 任务ID（添加链接到任务详情）
        yield TextField::new('taskId', '任务ID')
            ->setColumns('col-md-4')
            ->setHelp('关联的导入任务ID')
            ->formatValue(function ($value, $entity) {
                if (!$value) {
                    return '';
                }

                // 这里可以添加链接到相关任务，但需要在模板中实现
                return $value;
            })
        ;

        yield TextField::new('entityClass', '实体类名')
            ->setColumns('col-md-4')
            ->setHelp('导入数据对应的实体类')
            ->formatValue(function ($value) {
                // 只显示类名，不显示完整命名空间
                if (!is_string($value) || '' === $value) {
                    return '';
                }

                return basename(str_replace('\\', '/', $value));
            })
        ;

        yield IntegerField::new('line', '行号')
            ->setColumns('col-md-4')
            ->setHelp('发生错误的数据行号')
            ->formatValue(function ($value) {
                $numericValue = is_numeric($value) ? floatval($value) : 0.0;

                return number_format($numericValue);
            })
        ;

        // 错误信息
        yield TextareaField::new('error', '错误信息')
            ->setColumns('col-md-12')
            ->hideOnIndex()
            ->setNumOfRows(4)
            ->setHelp('详细的错误信息和堆栈跟踪')
        ;

        // 错误信息预览（仅在列表页显示）
        yield TextField::new('error', '错误摘要')
            ->onlyOnIndex()
            ->formatValue(function ($value) {
                if (!is_string($value) || '' === $value) {
                    return '';
                }

                // 只显示错误信息的前100个字符
                return mb_strlen($value) > 100 ? mb_substr($value, 0, 100) . '...' : $value;
            })
        ;

        // 原始数据
        yield ArrayField::new('rawRow', '原始数据')
            ->onlyOnDetail()
            ->setHelp('导入文件中的原始行数据')
        ;

        // 格式化数据
        yield ArrayField::new('newRow', '处理后数据')
            ->onlyOnDetail()
            ->setHelp('经过格式化处理后的数据')
        ;

        // 时间字段
        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL])
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('taskId', '任务ID'))
            ->add(TextFilter::new('entityClass', '实体类名'))
            ->add(NumericFilter::new('line', '行号'))
            ->add(TextFilter::new('error', '错误信息'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->orderBy('entity.id', 'DESC')
        ;
    }
}
