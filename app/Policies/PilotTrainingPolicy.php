<?php

namespace App\Policies;

use anlutro\LaravelSettings\Facade as Setting;
use App\Helpers\TrainingStatus;
use App\Models\PilotTraining;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PilotTrainingPolicy
{
    use HandlesAuthorization;

    public function view(User $user, PilotTraining $pilotTraining)
    {
        return $pilotTraining->instructors->contains($user) ||
            $user->isInstructorOrAbove() ||
            $user->is($pilotTraining->user);
    }

    public function edit(User $user)
    {
        return $user->isInstructorOrAbove();
    }

    public function create(User $user)
    {
        return $user->isInstructorOrAbove();
    }

    public function update(User $user)
    {
        return $user->isInstructorOrAbove();
    }

    public function store(User $user, $data)
    {
        if (! isset($data['user_id'])) {
            return true;
        }

        return $user->isInstructorOrAbove();
    }

    public function viewActiveRequests(User $user)
    {
        return $user->isInstructorOrAbove();
    }

    public function viewHistoricRequests(User $user)
    {
        return $user->isInstructorOrAbove();
    }

    public function apply(User $user)
    {
        $allowedSubDivisions = explode(',', Setting::get('trainingSubDivisions'));
        $divisionName = config('app.owner_name_short');

        if (! in_array($user->subdivision, $allowedSubDivisions) && $allowedSubDivisions != null) {
            $subdiv = 'none';
            if (isset($user->subdivision)) {
                $subdiv = $user->subdivision;
            }

            return Response::deny("You must join {$divisionName} to apply for training. You currently belong to " . $subdiv);
        }

        return ! $user->hasActivePilotTraining(true) ? Response::allow() : Response::deny('You have an active training request');
    }

    public function close(User $user, PilotTraining $training)
    {
        return $user->is($training->user) && $training->status == TrainingStatus::IN_QUEUE->value;
    }
}
