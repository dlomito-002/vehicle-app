<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleService;

class VehicleServicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VehicleService $service): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, VehicleService $service): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, VehicleService $service): bool
    {
        return $user->isAdmin();
    }
}
