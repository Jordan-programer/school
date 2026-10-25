<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /**
     * Listar alunos: Director, Subdirectores, Secretaria, Professores, Inspector.
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
            RoleEnum::PROVINCIAL_INSPECTOR->value,
        ]);
    }

    /**
     * Ver detalhes de um aluno.
     */
    public function view(User $user, Student $student): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Criar aluno (matrícula): Secretaria, Subdirector Administrativo, Director.
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
     * Actualizar dados do aluno: Secretaria, Subdirector Administrativo, Director.
     */
    public function update(User $user, Student $student): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value,
            RoleEnum::SECRETARY->value,
        ]);
    }

    /**
     * Remover aluno: apenas Director.
     */
    public function delete(User $user, Student $student): bool
    {
        return $user->hasRole(RoleEnum::DIRECTOR->value);
    }

    /**
     * Activar/desactivar matrícula do aluno.
     */
    public function toggleStatus(User $user, Student $student): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value,
            RoleEnum::SECRETARY->value,
        ]);
    }
}
