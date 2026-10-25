<?php

namespace App\Enums;

enum EnrollmentStatusEnum: string
{
    case CONFIRMADA  = 'CONFIRMADA';
    case PENDENTE    = 'PENDENTE';
    case CANCELADA   = 'CANCELADA';
    case TRANSFERIDO = 'TRANSFERIDO';

    public function label(): string
    {
        return match ($this) {
            self::CONFIRMADA  => 'Confirmada',
            self::PENDENTE    => 'Pendente de Confirmação',
            self::CANCELADA   => 'Cancelada / Anulada',
            self::TRANSFERIDO => 'Transferido',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
