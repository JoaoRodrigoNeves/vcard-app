<?php

namespace App\Policies;

use App\Models\DefaultCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DefaultCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->user_type == "A";
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return $user->user_type == "A";
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->user_type == "A";
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return $user->user_type == "A";
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return $user->user_type == "A";
    }
}
