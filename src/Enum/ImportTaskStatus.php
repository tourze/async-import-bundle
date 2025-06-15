<?php

namespace AsyncImportBundle\Enum;

use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;

enum ImportTaskStatus: string implements Labelable
{
    use ItemTrait;

    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::PROCESSING => '处理中',
            self::COMPLETED => '已完成',
            self::FAILED => '失败',
            self::CANCELLED => '已取消',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'secondary',
            self::PROCESSING => 'primary',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'warning',
        };
    }
}