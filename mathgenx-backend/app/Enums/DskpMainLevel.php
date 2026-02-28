<?php

namespace App\Enums;

enum DskpMainLevel: string
{
    case PRIMARY_YEAR_1 = 'pr1';
    case PRIMARY_YEAR_2 = 'pr2';
    case PRIMARY_YEAR_3 = 'pr3';
    case PRIMARY_YEAR_4 = 'pr4';
    case PRIMARY_YEAR_5 = 'pr5';
    case PRIMARY_YEAR_6 = 'pr6';
    case SECONDARY_YEAR_1 = 'sr1';
    case SECONDARY_YEAR_2 = 'sr2';
    case SECONDARY_YEAR_3 = 'sr3';
    case SECONDARY_YEAR_4 = 'sr4';
    case SECONDARY_YEAR_5 = 'sr5';

    public function label(): string
    {
        return match ($this) {
            self::PRIMARY_YEAR_1 => 'Sekolah Rendah Darjah 1',
            self::PRIMARY_YEAR_2 => 'Sekolah Rendah Darjah 2',
            self::PRIMARY_YEAR_3 => 'Sekolah Rendah Darjah 3',
            self::PRIMARY_YEAR_4 => 'Sekolah Rendah Darjah 4',
            self::PRIMARY_YEAR_5 => 'Sekolah Rendah Darjah 5',
            self::PRIMARY_YEAR_6 => 'Sekolah Rendah Darjah 6',
            self::SECONDARY_YEAR_1 => 'Sekolah Menengah Tingkatan 1',
            self::SECONDARY_YEAR_2 => 'Sekolah Menengah Tingkatan 2',
            self::SECONDARY_YEAR_3 => 'Sekolah Menengah Tingkatan 3',
            self::SECONDARY_YEAR_4 => 'Sekolah Menengah Tingkatan 4',
            self::SECONDARY_YEAR_5 => 'Sekolah Menengah Tingkatan 5',
        };
    }

    public static function options(): array
    {
        return array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
