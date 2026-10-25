<?php

namespace App\Enums;

enum RoleEnum: string
{
    case DIRECTOR = 'Director';
    case PEDAGOGIC_SUBDIRECTOR = 'Subdirector Pedagógico';
    case ADMINISTRATIVE_SUBDIRECTOR = 'Subdirector Administrativo';
    case SECRETARY = 'Secretaria';
    case TEACHER = 'Professor';
    case CLASS_COUNCIL_LEADER = 'Director de Turma';
    case COORDINATOR = 'Coordenador';
    case TREASURER = 'Tesoureiro';
    case GUARDIAN = 'Encarregado';
    case STUDENT = 'Aluno';
    case PROVINCIAL_INSPECTOR = 'Gestor Municipal/Provincial';

    /**
     * Retorna lista de todos os valores dos papéis em Angola.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
