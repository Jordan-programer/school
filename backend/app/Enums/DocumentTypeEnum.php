<?php

namespace App\Enums;

enum DocumentTypeEnum: string
{
    case BI        = 'BI';
    case CEDULA    = 'CEDULA';
    case ASSENTO   = 'ASSENTO';
    case PROVISORIO = 'PROVISORIO';

    public function label(): string
    {
        return match($this) {
            self::BI        => 'Bilhete de Identidade',
            self::CEDULA    => 'Cédula Pessoal',
            self::ASSENTO   => 'Assento de Nascimento',
            self::PROVISORIO => 'Número Provisório',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
