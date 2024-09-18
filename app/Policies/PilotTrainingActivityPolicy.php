<?php

namespace App\Policies;

use App\Models\PilotTraining;
use App\Models\User;

class PilotTrainingActivityPolicy
{
    public function comment(User $user, PilotTraining $training)
    {
        return $training->instructors->contains($user) ||
        $user->can('update', [PilotTraining::class, $training]);
    }

    public function view(User $user, PilotTraining $training, string $type)
    {
        if ($type == 'COMMENT') {
            return $training->instructors->contains($user) || $user->isInstructor();
        }

        return true;
    }
}
