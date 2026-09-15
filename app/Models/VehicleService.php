<?php

namespace App\Models;

use App\Enums\ServiceType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleService extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * A service is flagged "próximo" (due soon) when the vehicle is within
     * this many kilometers or this many days of the registered next-due
     * threshold, and "vencido" (overdue) once it's past it.
     */
    public const MILEAGE_ALERT_WINDOW = 500;
    public const DATE_ALERT_WINDOW_DAYS = 15;

    protected $fillable = [
        'vehicle_id',
        'created_by',
        'service_type',
        'other_description',
        'service_date',
        'mileage_at_service',
        'next_service_date',
        'next_service_mileage',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'service_date' => 'date',
            'mileage_at_service' => 'integer',
            'next_service_date' => 'date',
            'next_service_mileage' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return $this->service_type === ServiceType::Other && $this->other_description
            ? $this->other_description
            : $this->service_type->label();
    }

    /**
     * Alert status against the vehicle's current mileage and today's date:
     * 'overdue' | 'due_soon' | 'ok'.
     */
    public function alertStatus(?int $currentMileage = null): string
    {
        $currentMileage ??= $this->vehicle?->currentMileage();

        $mileageRemaining = $this->next_service_mileage !== null && $currentMileage !== null
            ? $this->next_service_mileage - $currentMileage
            : null;

        $daysRemaining = $this->next_service_date !== null
            ? Carbon::today()->diffInDays($this->next_service_date, false)
            : null;

        if (($mileageRemaining !== null && $mileageRemaining <= 0)
            || ($daysRemaining !== null && $daysRemaining <= 0)) {
            return 'overdue';
        }

        if (($mileageRemaining !== null && $mileageRemaining <= self::MILEAGE_ALERT_WINDOW)
            || ($daysRemaining !== null && $daysRemaining <= self::DATE_ALERT_WINDOW_DAYS)) {
            return 'due_soon';
        }

        return 'ok';
    }
}
