<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\User;

class UserPolicy
{
    /**
     * Determina se o utilizador pode visualizar a listagem geral de contas.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::PEDAGOGIC_SUBDIRECTOR->value,
            RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value,
            RoleEnum::SECRETARY->value,
            RoleEnum::PROVINCIAL_INSPECTOR->value,
        ]);
    }

    /**
     * Determina se o utilizador pode visualizar os detalhes de outro utilizador.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $this->viewAny($user);
    }

    /**
     * Determina se o utilizador pode criar novos utilizadores no sistema.
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
     * Determina se o utilizador pode actualizar dados de uma conta.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value,
        ]);
    }

    /**
     * Determina se o utilizador pode apagar/desactivar logicamente uma conta.
     */
    public function delete(User $user, User $model): bool
    {
        // Nunca apagar a própria conta
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasRole(RoleEnum::DIRECTOR->value);
    }

    /**
     * Determina se pode alterar o estado activo/inactivo de uma conta.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasAnyRole([
            RoleEnum::DIRECTOR->value,
            RoleEnum::ADMINISTRATIVE_SUBDIRECTOR->value,
        ]);
    }
}
