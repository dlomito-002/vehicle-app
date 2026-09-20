<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMaintenanceCompletion extends Model
{
    protected $fillable = [
        'vehicle_maintenance_schedule_id',
        'mileage',
        'service_date',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'mileage' => 'integer',
            'service_date' => 'date',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(VehicleMaintenanceSchedule::class, 'vehicle_maintenance_schedule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
