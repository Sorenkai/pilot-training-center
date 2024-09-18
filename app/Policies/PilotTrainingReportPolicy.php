<?php

namespace App\Policies;

use App\Models\PilotTraining;
use App\Models\PilotTrainingReport;
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

    public function update(User $user, PilotTrainingReport $trainingReport)
    {
        return $trainingReport->pilotTraining->instructors->contains($user) ||
                $user->isAdmin() ||
                $user->isInstructor();
    }

    public function delete(User $user, PilotTrainingReport $trainingReport)
    {
        return ($user->isAdmin() || $user->isInstructor())
            ? Response::allow()
            : Response::deny('Only instructors can delete training reports.');
    }
}
