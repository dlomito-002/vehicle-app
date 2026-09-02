<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleDelivery;

class VehicleDeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VehicleDelivery $delivery): bool
    {
        return $user->isAdmin() || $delivery->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, VehicleDelivery $delivery): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, VehicleDelivery $delivery): bool
    {
        return $user->isAdmin();
    }
}
