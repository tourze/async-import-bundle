<?php

declare(strict_types=1);

namespace AsyncImportBundle\Controller\Admin;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Enum\ImportFileType;
use AsyncImportBundle\Enum\ImportTaskStatus;
use AsyncImportBundle\Service\AsyncImportService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;

/**
 * 异步导入任务管理控制器
 */
#[AdminCrud(routePath: '/async-import/task', routeName: 'async_import_task')]
#[Autoconfigure(public: true)]
final class AsyncImportTaskCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AsyncImportService $asyncImportService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AsyncImportTask::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('导入任务')
            ->setEntityLabelInPlural('导入任务')
            ->setPageTitle('index', '导入任务管理')
            ->setPageTitle('detail', '导入任务详情')
            ->setPageTitle('edit', '编辑导入任务')
            ->setPageTitle('new', '新建导入任务')
            ->setHelp('index', '管理系统中的异步导入任务，查看导入进度和处理结果')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'file', 'entityClass', 'userId', 'remark'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield from $this->getCommonFields($pageName);
        yield from $this->getProgressFields($pageName);
        yield from $this->getRetryFields();
        yield from $this->getTimeFields();
        yield from $this->getConfigFields();
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getCommonFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->setMaxLength(9999)->onlyOnIndex();
        yield TextField::new('userId', '用户ID')->setColumns('col-md-6')->setHelp('执行导入任务的用户ID');
        yield TextField::new('file', '文件路径')
            ->setColumns('col-md-6')
            ->setHelp('导入文件的完整路径')
            ->formatValue(fn ($value) => is_string($value) && '' !== $value ? basename($value) : '')
        ;
        yield TextField::new('entityClass', '实体类名')->setColumns('col-md-6')->setHelp('导入数据对应的实体类');

        $statusField = EnumField::new('status', '任务状态')->setColumns('col-md-6')->hideOnIndex();
        $statusField->setEnumCases(ImportTaskStatus::cases());
        yield $statusField;

        $fileTypeField = EnumField::new('fileType', '文件类型')->setColumns('col-md-6');
        $fileTypeField->setEnumCases(ImportFileType::cases());
        yield $fileTypeField;
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getProgressFields(string $pageName): iterable
    {
        if (Crud::PAGE_INDEX === $pageName) {
            yield TextField::new('remark', '状态/进度')
                ->formatValue(function ($value, $entity) {
                    /** @var AsyncImportTask $entity */
                    $status = $entity->getStatus();
                    $statusHtml = sprintf('<span class="badge badge-%s">%s</span>', $status->color(), $status->getLabel());
                    $processed = number_format($entity->getProcessCount() ?? 0);
                    $total = number_format($entity->getTotalCount() ?? 0);
                    $percentage = $this->asyncImportService->getTaskProgressPercentage($entity);

                    return $statusHtml . sprintf('<br><small>%s / %s (%.1f%%)</small>', $processed, $total, $percentage);
                })
            ;
        } else {
            yield from $this->getDetailProgressFields();
        }
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getDetailProgressFields(): iterable
    {
        $formatter = fn ($value) => number_format(is_numeric($value) ? floatval($value) : 0.0);
        yield IntegerField::new('totalCount', '总行数')->setColumns('col-md-3')->formatValue($formatter);
        yield IntegerField::new('processCount', '已处理')->setColumns('col-md-3')->formatValue($formatter);
        yield IntegerField::new('successCount', '成功数')->setColumns('col-md-3')->formatValue($formatter);
        yield IntegerField::new('failCount', '失败数')->setColumns('col-md-3')->formatValue($formatter);
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getRetryFields(): iterable
    {
        yield IntegerField::new('retryCount', '重试次数')->setColumns('col-md-6');
        yield IntegerField::new('maxRetries', '最大重试')->setColumns('col-md-6');
        yield IntegerField::new('priority', '优先级')->setColumns('col-md-6')->setHelp('数值越大优先级越高，范围：-100 到 100');
        yield IntegerField::new('memoryUsage', '内存占用')
            ->setColumns('col-md-6')
            ->formatValue(fn ($value) => $this->formatBytes(is_numeric($value) ? intval($value) : 0))
            ->setHelp('任务执行时的内存使用情况')
        ;
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getTimeFields(): iterable
    {
        $format = 'yyyy-MM-dd HH:mm:ss';
        yield DateTimeField::new('startTime', '开始时间')->setColumns('col-md-6')->setFormat($format)->hideOnForm();
        yield DateTimeField::new('endTime', '结束时间')->setColumns('col-md-6')->setFormat($format)->hideOnForm();
        yield DateTimeField::new('lastErrorTime', '最后错误时间')->setColumns('col-md-6')->setFormat($format)->hideOnForm();
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getConfigFields(): iterable
    {
        yield ArrayField::new('importConfig', '导入配置')->onlyOnDetail()->setHelp('任务的配置参数');
        yield TextareaField::new('lastErrorMessage', '最后错误信息')->setColumns('col-md-12')->hideOnIndex()->setNumOfRows(3);
        yield TextareaField::new('remark', '备注')->setColumns('col-md-12')->hideOnIndex()->setNumOfRows(3);
        yield DateTimeField::new('createTime', '创建时间')->setFormat('yyyy-MM-dd HH:mm:ss')->hideOnForm()->onlyOnDetail();
        yield DateTimeField::new('updateTime', '更新时间')->setFormat('yyyy-MM-dd HH:mm:ss')->hideOnForm()->onlyOnDetail();
    }

    public function configureActions(Actions $actions): Actions
    {
        // 重试任务操作
        $retryAction = Action::new('retryTask', '重试任务')
            ->linkToCrudAction('retryTask')
            ->setCssClass('btn btn-warning btn-sm')
            ->setIcon('fas fa-redo')
            ->displayIf(function (AsyncImportTask $entity) {
                return $this->asyncImportService->canTaskRetry($entity);
            })
        ;

        // 取消任务操作
        $cancelAction = Action::new('cancelTask', '取消任务')
            ->linkToCrudAction('cancelTask')
            ->setCssClass('btn btn-secondary btn-sm')
            ->setIcon('fas fa-times')
            ->displayIf(function (AsyncImportTask $entity) {
                return in_array($entity->getStatus(), [ImportTaskStatus::PENDING, ImportTaskStatus::PROCESSING], true);
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $retryAction)
            ->add(Crud::PAGE_INDEX, $cancelAction)
            ->add(Crud::PAGE_DETAIL, $retryAction)
            ->add(Crud::PAGE_DETAIL, $cancelAction)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        // 状态过滤器
        $statusChoices = [];
        foreach (ImportTaskStatus::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        // 文件类型过滤器
        $fileTypeChoices = [];
        foreach (ImportFileType::cases() as $case) {
            $fileTypeChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(TextFilter::new('userId', '用户ID'))
            ->add(TextFilter::new('entityClass', '实体类名'))
            ->add(ChoiceFilter::new('status', '任务状态')->setChoices($statusChoices))
            ->add(ChoiceFilter::new('fileType', '文件类型')->setChoices($fileTypeChoices))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('startTime', '开始时间'))
            ->add(DateTimeFilter::new('endTime', '结束时间'))
        ;
    }

    /**
     * 重试任务
     */
    #[AdminAction(routeName: 'async_import_task_retry', routePath: '{entityId}/retry')]
    public function retryTask(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof AsyncImportTask);

        if (!$this->asyncImportService->canTaskRetry($entity)) {
            $this->addFlash('warning', '该任务无法重试');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? $this->generateUrl('admin'));
        }

        // 重置任务状态
        $entity->setStatus(ImportTaskStatus::PENDING);
        $this->asyncImportService->incrementTaskRetryCount($entity);
        $entity->setLastErrorMessage(null);
        $entity->setLastErrorTime(null);

        $this->entityManager->flush();

        $this->addFlash('success', '任务已重新加入队列，等待处理');

        return $this->redirect($context->getRequest()->headers->get('referer') ?? $this->generateUrl('admin'));
    }

    /**
     * 取消任务
     */
    #[AdminAction(routeName: 'async_import_task_cancel', routePath: '{entityId}/cancel')]
    public function cancelTask(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof AsyncImportTask);

        if (!in_array($entity->getStatus(), [ImportTaskStatus::PENDING, ImportTaskStatus::PROCESSING], true)) {
            $this->addFlash('warning', '只能取消待处理或处理中的任务');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? $this->generateUrl('admin'));
        }

        $entity->setStatus(ImportTaskStatus::CANCELLED);
        $this->entityManager->flush();

        $this->addFlash('success', '任务已取消');

        return $this->redirect($context->getRequest()->headers->get('referer') ?? $this->generateUrl('admin'));
    }

    /**
     * 格式化字节大小
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            ++$unitIndex;
        }

        return sprintf('%.2f %s', $bytes, $units[$unitIndex]);
    }
}
