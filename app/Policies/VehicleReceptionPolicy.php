<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleReception;

class VehicleReceptionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VehicleReception $reception): bool
    {
        return $user->isAdmin() || $reception->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        // Any authenticated agent or admin can log a reception.
        return true;
    }

    public function update(User $user, VehicleReception $reception): bool
    {
        // Submitted forms are immutable for agents; only admins may amend them.
        return $user->isAdmin();
    }

    public function delete(User $user, VehicleReception $reception): bool
    {
        return $user->isAdmin();
    }
}
