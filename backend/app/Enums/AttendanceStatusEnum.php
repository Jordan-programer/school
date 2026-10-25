<?php

namespace App\Enums;

enum AttendanceStatusEnum: string
{
    case PRESENTE            = 'PRESENTE';
    case FALTA_INJUSTIFICADA = 'FALTA_INJUSTIFICADA';
    case FALTA_JUSTIFICADA   = 'FALTA_JUSTIFICADA';
    case ATRASO              = 'ATRASO';

    public function label(): string
    {
        return match ($this) {
            self::PRESENTE            => 'Presente',
            self::FALTA_INJUSTIFICADA => 'Falta Injustificada',
            self::FALTA_JUSTIFICADA   => 'Falta Justificada',
            self::ATRASO              => 'Atraso / Meia Falta',
        };
    }

    public function abbreviation(): string
    {
        return match ($this) {
            self::PRESENTE            => 'P',
            self::FALTA_INJUSTIFICADA => 'F',
            self::FALTA_JUSTIFICADA   => 'FJ',
            self::ATRASO              => 'A',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
