<?php

namespace App\Policies;

use App\Models\Billing;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Billing  $billing
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Billing $billing)
    {
        // Admins can delete any billing record, or a user can delete their own.
        return $user->role === 'admin' || $user->id === $billing->user_id;
    }
}
