<?php

namespace App\Policies;

use App\Models\EvaluationRubric;
use App\Models\User;

class RubricPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRubrics();
    }

    public function view(User $user, EvaluationRubric $rubric): bool
    {
        return $user->canManageRubrics();
    }

    public function create(User $user): bool
    {
        return $user->canManageRubrics();
    }

    public function update(User $user, EvaluationRubric $rubric): bool
    {
        return $user->canManageRubrics();
    }

    public function archive(User $user, EvaluationRubric $rubric): bool
    {
        return $user->canManageRubrics() && $rubric->is_active;
    }
}
