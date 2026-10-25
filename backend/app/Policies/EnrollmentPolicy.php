<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Visualizar matrículas e confirmações.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::PEDAGOGIC_SUBDIRECTOR->value,
            RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value,
            RoleEnum::SECRETARY->value,
            RoleEnum::TEACHER->value,
            RoleEnum::CLASS_COUNCIL_LEADER->value,
            RoleEnum::COORDINATOR->value,
            RoleEnum::PROVINCIAL_INSPECTOR->value,
        ]);
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Realizar nova matrícula.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value,
            RoleEnum::SECRETARY->value,
        ]);
    }

    /**
     * Confirmar matrícula (renovação).
     */
    public function confirm(User $user, Enrollment $enrollment): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::PEDAGOGIC_SUBDIRECTOR->value,
            RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value,
            RoleEnum::SECRETARY->value,
        ]);
    }

    /**
     * Distribuir ou alterar turma do aluno.
     */
    public function assignClass(User $user, Enrollment $enrollment): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::PEDAGOGIC_SUBDIRECTOR->value,
            RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value,
            RoleEnum::SECRETARY->value,
        ]);
    }

    /**
     * Cancelar ou anular matrícula.
     */
    public function cancel(User $user, Enrollment $enrollment): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value,
        ]);
    }

    /**
     * Registar transferência escolar.
     */
    public function transfer(User $user, Enrollment $enrollment): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value,
            RoleEnum::SECRETARY->value,
        ]);
    }
}
