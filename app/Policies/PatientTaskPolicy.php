<?php

namespace App\Policies;

use App\Models\PatientTask;
use App\Models\User;

class PatientTaskPolicy
{
    public function view(User $user, PatientTask $task): bool
    {
        return (int) $task->patient_user_id === (int) $user->id;
    }

    public function update(User $user, PatientTask $task): bool
    {
        return (int) $task->patient_user_id === (int) $user->id;
    }

    public function delete(User $user, PatientTask $task): bool
    {
        return (int) $task->patient_user_id === (int) $user->id;
    }
}
