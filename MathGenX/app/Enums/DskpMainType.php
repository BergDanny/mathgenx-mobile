<?php

namespace App\Enums;

enum DskpMainType: string
{
    case KSSM = 'kssm';
    case KSSR = 'kssr';

    public function label(): string
    {
        return match ($this) {
            self::KSSM => 'Kurikulum Standard Sekolah Menengah (KSSM)',
            self::KSSR => 'Kurikulum Standard Sekolah Rendah (KSSR)',
        };
    }

    public static function options(): array
    {
        return array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
