<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManagementReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can restore the model.
     *
     * @return bool
     */
    public function accessTrainingReports(User $user, $filterArea)
    {
        if ($filterArea) {
            return $user->isAdmin();
        }

        return $user->isAdmin();
    }

    public function viewInstructors(User $user)
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return bool
     */
    public function viewAccessReport(User $user)
    {
        return $user->isModeratorOrAbove();
    }
}
