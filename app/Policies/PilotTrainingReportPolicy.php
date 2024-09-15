<?php

namespace App\Policies;

use App\Models\PilotTrainingReport;
use App\Models\PilotTraining;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PilotTrainingReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, PilotTraining $training)
    {
        return $training->instructors->contains($user) ||
            $user->is($training->user) ||
            $user->isInstructor() ||
            $user->isAdmin();
    }

    public function create(User $user)
    {
        return $user->isAdmin() || $user->isInstructor();
    }
}
