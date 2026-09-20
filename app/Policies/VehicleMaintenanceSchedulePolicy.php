<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleMaintenanceSchedule;

class VehicleMaintenanceSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VehicleMaintenanceSchedule $schedule): bool
    {
        return true;
    }

    public function update(User $user, VehicleMaintenanceSchedule $schedule): bool
    {
        return $user->isAdmin();
    }
}
