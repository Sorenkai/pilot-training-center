<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PilotTraining;
use Illuminate\Auth\Access\HandlesAuthorization;

class PilotTrainingPolicy
{
    use HandlesAuthorization;
    
    public function view(User $user, PilotTraining $pilotTraining)
    {
        return $pilotTraining->instructors->contains($user) ||
            $user->isModeratorOrAbove() ||
            $user->is($pilotTraining->user);
    }

    public function edit(User $user, PilotTraining $pilotTraining)
    {
        return $user->isModeratorOrAbove();
    }

    public function update(User $user, PilotTraining $pilotTraining)
    {
        return $user->isModeratorOrAbove();
    }
    

    public function store(User $user, $data)
    {
        if (! isset($data['user_id'])) {
            return true;
        }

        return $user->isModeratorOrAbove();
    }

    public function viewActiveRequests(User $user)
    {
        return $user->isInstructor();
    }
}
