<?php

namespace App\Models;

use App\Enums\ReceptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'make',
        'model',
        'license_plate',
    ];

    public function receptions(): HasMany
    {
        return $this->hasMany(VehicleReception::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(VehicleDelivery::class);
    }

    /** Receptions for this vehicle that have not yet been closed by a delivery. */
    public function openReceptions(): HasMany
    {
        return $this->receptions()->where('status', ReceptionStatus::Open);
    }

    public function displayName(): string
    {
        return trim("{$this->make} {$this->license_plate}");
    }
}
