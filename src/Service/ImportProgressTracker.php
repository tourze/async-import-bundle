<?php

namespace AsyncImportBundle\Service;

use AsyncImportBundle\Entity\AsyncImportTask;
use AsyncImportBundle\Event\ImportProgressEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * 导入进度追踪服务
 */
class ImportProgressTracker
{
    private array $progress = [];
    
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * 更新进度
     */
    public function updateProgress(
        AsyncImportTask $task,
        int $processed,
        int $success,
        int $failed,
        bool $dispatchEvent = true
    ): void {
        $taskId = $task->getId();

        if (!isset($this->progress[$taskId])) {
            $this->startTracking($task);
        }

        $now = microtime(true);
        $tracker = &$this->progress[$taskId];

        // 计算处理速度
        $timeDiff = $now - $tracker['lastUpdate'];
        $rowsDiff = $processed - $tracker['processed'];

        if ($timeDiff > 0) {
            $tracker['speed'] = round($rowsDiff / $timeDiff, 2);
        }

        // 更新数据
        $tracker['processed'] = $processed;
        $tracker['success'] = $success;
        $tracker['failed'] = $failed;
        $tracker['lastUpdate'] = $now;
        $tracker['lastBatch'] = $rowsDiff;

        // 计算预计剩余时间
        $remaining = $task->getTotalCount() - $processed;
        $eta = $tracker['speed'] > 0 ? round($remaining / $tracker['speed']) : null;

        // 发送进度事件
        if ($dispatchEvent) {
            $event = new ImportProgressEvent(
                $task,
                $processed,
                $success,
                $failed,
                $tracker['speed'],
                $eta !== null ? (int) $eta : null
            );

            $this->eventDispatcher->dispatch($event);
        }
    }

    /**
     * 开始追踪任务
     */
    public function startTracking(AsyncImportTask $task): void
    {
        $this->progress[$task->getId()] = [
            'startTime' => microtime(true),
            'lastUpdate' => microtime(true),
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'lastBatch' => 0,
            'speed' => 0
        ];
    }

    /**
     * 停止追踪
     */
    public function stopTracking(AsyncImportTask $task): array
    {
        $taskId = $task->getId();
        
        if (!isset($this->progress[$taskId])) {
            return [];
        }
        
        $tracker = $this->progress[$taskId];
        $tracker['endTime'] = microtime(true);
        $tracker['duration'] = $tracker['endTime'] - $tracker['startTime'];
        $tracker['averageSpeed'] = $tracker['processed'] / $tracker['duration'];
        
        unset($this->progress[$taskId]);
        
        return $tracker;
    }

    /**
     * 获取任务进度信息
     */
    public function getProgress(AsyncImportTask $task): array
    {
        $taskId = $task->getId();
        
        if (!isset($this->progress[$taskId])) {
            return [
                'processed' => $task->getProcessCount(),
                'success' => $task->getSuccessCount(),
                'failed' => $task->getFailCount(),
                'total' => $task->getTotalCount(),
                'percentage' => $task->getProgressPercentage(),
                'speed' => 0,
                'eta' => null
            ];
        }
        
        $tracker = $this->progress[$taskId];
        $remaining = $task->getTotalCount() - $tracker['processed'];
        $eta = $tracker['speed'] > 0 ? round($remaining / $tracker['speed']) : null;
        
        return [
            'processed' => $tracker['processed'],
            'success' => $tracker['success'],
            'failed' => $tracker['failed'],
            'total' => $task->getTotalCount(),
            'percentage' => $task->getProgressPercentage(),
            'speed' => $tracker['speed'],
            'eta' => $eta,
            'duration' => microtime(true) - $tracker['startTime']
        ];
    }

    /**
     * 获取所有正在追踪的任务ID
     */
    public function getTrackingTaskIds(): array
    {
        return array_keys($this->progress);
    }
}