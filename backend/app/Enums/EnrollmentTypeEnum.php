<?php

namespace App\Enums;

enum EnrollmentTypeEnum: string
{
    case NOVA_MATRICULA = 'NOVA_MATRICULA';
    case CONFIRMACAO    = 'CONFIRMACAO';
    case TRANSFERENCIA  = 'TRANSFERENCIA';

    public function label(): string
    {
        return match ($this) {
            self::NOVA_MATRICULA => 'Nova Matrícula (Novo Ingresso)',
            self::CONFIRMACAO    => 'Confirmação (Renovação)',
            self::TRANSFERENCIA  => 'Entrada por Transferência',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
