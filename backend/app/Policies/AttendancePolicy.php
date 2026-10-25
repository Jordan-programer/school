<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Consultar registos de chamada.
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

    public function view(User $user, Attendance $attendance): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Registar chamada diária (presenças e faltas).
     */
    public function record(User $user): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::PEDAGOGIC_SUBDIRECTOR->value,
            RoleEnum::TEACHER->value,
            RoleEnum::CLASS_COUNCIL_LEADER->value,
            RoleEnum::COORDINATOR->value,
            RoleEnum::SECRETARY->value,
        ]);
    }

    /**
     * Justificar faltas de alunos.
     */
    public function justify(User $user): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::PEDAGOGIC_SUBDIRECTOR->value,
            RoleEnum::CLASS_COUNCIL_LEADER->value,
            RoleEnum::SECRETARY->value,
        ]);
    }
}
