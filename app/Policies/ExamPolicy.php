<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        return $user->isAdmin() || $user->isInstructor();
    }
    
    public function store(User $user)
    {
        return $user->isAdmin() || $user->isInstructor();
    }
}
