<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;
use App\Models\Attendance;


class AttendancePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user, Attendance $attendance)
    {
        return $user->id === $attendance->marked_by || $user->role === 'admin';
    }
}
