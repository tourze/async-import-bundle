<?php

namespace AsyncImportBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ImportFileType: string implements Labelable, Itemable, Selectable
{
    use SelectTrait;

    case CSV = 'csv';
    case EXCEL = 'excel';
    case XLS = 'xls';
    case XLSX = 'xlsx';
    case JSON = 'json';

    public function getLabel(): string
    {
        return match ($this) {
            self::CSV => 'CSV',
            self::EXCEL => 'Excel (自动识别)',
            self::XLS => 'Excel 97-2003',
            self::XLSX => 'Excel 2007+',
            self::JSON => 'JSON',
        };
    }

    public function mimeTypes(): array
    {
        return match ($this) {
            self::CSV => ['text/csv', 'application/csv'],
            self::EXCEL => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            self::XLS => ['application/vnd.ms-excel'],
            self::XLSX => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            self::JSON => ['application/json'],
        };
    }

    public function extensions(): array
    {
        return match ($this) {
            self::CSV => ['csv'],
            self::EXCEL => ['xls', 'xlsx'],
            self::XLS => ['xls'],
            self::XLSX => ['xlsx'],
            self::JSON => ['json'],
        };
    }
}