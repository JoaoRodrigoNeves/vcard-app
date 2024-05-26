<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vcard;

class VcardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->user_type == "A";
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vcard $vcard): bool
    {
        return $user->user_type == "A" || $user->id == $vcard->phone_number;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vcard $vcard): bool
    {
        return $user->user_type == "A" || $user->id == $vcard->phone_number;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vcard $vcard): bool
    {
        return $user->user_type == "A" || $user->id == $vcard->phone_number;
    }

}
