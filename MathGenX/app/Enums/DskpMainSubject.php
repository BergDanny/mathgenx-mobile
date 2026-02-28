<?php

namespace App\Enums;

enum DskpMainSubject: string
{
    case MATHEMATICS = 'matematik';

    public function label(): string
    {
        return match ($this) {
            self::MATHEMATICS => 'Matematik',
        };
    }

    public static function options(): array
    {
        return array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
