<?php

namespace App\Models;

use App\Enums\ReceptionStatus;
use Illuminate\Database\Eloquent\Builder;
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

    public function services(): HasMany
    {
        return $this->hasMany(VehicleService::class);
    }

    /** Receptions for this vehicle that have not yet been closed by a delivery. */
    public function openReceptions(): HasMany
    {
        return $this->receptions()->where('status', ReceptionStatus::Open);
    }

    /**
     * A vehicle can't be requested again until it's returned: it's only
     * available when it has no open reception.
     */
    public function isAvailable(): bool
    {
        return ! $this->openReceptions()->exists();
    }

    /** Scope: only vehicles with no open reception right now. */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereDoesntHave('receptions', fn ($q) => $q->where('status', ReceptionStatus::Open));
    }

    public function displayName(): string
    {
        return trim("{$this->make} {$this->license_plate}");
    }

    /**
     * Best-known current odometer reading, used to compute service alerts.
     * Mileage is expected to be monotonically increasing across a vehicle's
     * history, so the highest recorded value (whether from a delivery's
     * final_mileage or a reception's initial_mileage) is the most current.
     */
    public function currentMileage(): ?int
    {
        $latestDelivery = (int) ($this->deliveries()->max('final_mileage') ?? 0);
        $latestReception = (int) ($this->receptions()->max('initial_mileage') ?? 0);

        $max = max($latestDelivery, $latestReception);

        return $max > 0 ? $max : null;
    }
}

