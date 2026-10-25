<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\ClassGroup;
use App\Models\User;

class ClassGroupPolicy
{
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

    public function view(User $user, ClassGroup $classGroup): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::PEDAGOGIC_SUBDIRECTOR->value,
            RoleEnum::SECRETARY->value,
        ]);
    }

    public function update(User $user, ClassGroup $classGroup): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::PEDAGOGIC_SUBDIRECTOR->value,
        ]);
    }

    public function delete(User $user, ClassGroup $classGroup): bool
    {
        return $user->hasRole(RoleEnum::DIRECTOR->value);
    }

    public function assignTeacher(User $user, ClassGroup $classGroup): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::PEDAGOGIC_SUBDIRECTOR->value,
        ]);
    }
}
