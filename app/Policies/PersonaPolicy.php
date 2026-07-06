<?php

namespace App\Policies;

use App\Models\Persona;
use App\Models\User;

class PersonaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManagePersonas();
    }

    public function view(User $user, Persona $persona): bool
    {
        return $user->canManagePersonas();
    }

    public function create(User $user): bool
    {
        return $user->canManagePersonas();
    }

    public function update(User $user, Persona $persona): bool
    {
        return $user->canManagePersonas() && $persona->isActive();
    }

    public function delete(User $user, Persona $persona): bool
    {
        return $user->canManagePersonas();
    }

    public function archive(User $user, Persona $persona): bool
    {
        return $user->canManagePersonas() && $persona->isActive();
    }

    public function duplicate(User $user, Persona $persona): bool
    {
        return $user->canManagePersonas();
    }
}
