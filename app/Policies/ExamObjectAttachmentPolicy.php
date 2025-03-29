<?php

namespace App\Policies;

use App\Models\ExamObjectAttachment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamObjectAttachmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ExamObjectAttachment $attachment)
    {
        return ($user->can('view', $attachment->object) && $attachment->hidden != true) || $user->isInstructor();
    }

    public function create(User $user)
    {
        return $user->isInstructorOrAbove();
    }

    public function delete(User $user, ExamObjectAttachment $attachment)
    {
        return $user->isInstructorOrAbove() || $user->is($attachment->file->owner);
    }
}
