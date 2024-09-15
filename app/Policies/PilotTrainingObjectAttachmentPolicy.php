<?php

namespace App\Policies;

use App\Models\PilotTrainingObjectAttachment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PilotTrainingObjectAttachmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, PilotTrainingObjectAttachment $attachment)
    {
        return ($user->can('view', $attachment->object) && $attachment->hidden != true) || $user->isInstructor();
    }

    public function create(User $user)
    {
        return $user->isInstructorOrAbove();
    }

    public function delete(User $user, PilotTrainingObjectAttachment $attachment)
    {
        return $user->isInstructorOrAbove() || $user->is($attachment->file->owner);
    }
}
