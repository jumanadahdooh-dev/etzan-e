<?php

namespace App\Policies;

use App\Models\PatientMeal;
use App\Models\User;

class PatientMealPolicy
{
    public function view(User $user, PatientMeal $meal): bool
    {
        return (int) $meal->user_id === (int) $user->id;
    }

    public function update(User $user, PatientMeal $meal): bool
    {
        return (int) $meal->user_id === (int) $user->id;
    }

    public function delete(User $user, PatientMeal $meal): bool
    {
        return (int) $meal->user_id === (int) $user->id;
    }
}
