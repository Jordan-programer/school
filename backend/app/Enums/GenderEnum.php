<?php

namespace App\Enums;

enum GenderEnum: string
{
    case MASCULINO = 'M';
    case FEMININO  = 'F';

    public function label(): string
    {
        return match($this) {
            self::MASCULINO => 'Masculino',
            self::FEMININO  => 'Feminino',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
