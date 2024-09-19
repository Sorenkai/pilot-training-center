<?php

namespace App\Policies;

use App\Models\Endorsement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EndorsementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view endorsements details.
     *
     * @return bool
     */
    public function view(User $user)
    {
        return $user == Auth::user() || $user->isInstructorOrAbove();
    }

    /**
     * Determine whether the user can create endorsements.
     *
     * @return bool
     */
    public function create(User $user, $type = null)
    {
        if ($type == 'VISITING' || $type == 'EXAMINER') {
            return $user->isAdmin();
        }

        return $user->isAdmin();
    }

    /**
     * Determine whether the user can revoke endoersements.
     *
     * @return bool
     */
    public function delete(User $user, Endorsement $endorsement)
    {

        // Check if user got correct permissions
        if ($endorsement->type == 'VISITING' || $endorsement->type == 'EXAMINER') {
            return $user->isAdmin();
        }

        return $user->isAdmin();
    }

    /**
     * Determine whether the user can shorten endoersement date.
     *
     * @return bool
     */
    public function shorten(User $user, Endorsement $endorsement)
    {
        return $this->delete($user, $endorsement);
    }
}
