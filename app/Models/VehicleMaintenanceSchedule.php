<?php

namespace App\Models;

use App\Enums\MaintenanceCategory;
use App\Mail\MaintenanceAlertMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Mail;

/**
 * One row per vehicle + MaintenanceCategory (basic/major/transmission).
 * This coexists with the free-text VehicleService log — it doesn't replace
 * it — and tracks a fixed km interval per category, computing the next due
 * mileage from the last completed service of that category (never from the
 * vehicle's current mileage directly).
 */
class VehicleMaintenanceSchedule extends Model
{
    /** Vehicle enters the warning window this many km before the next due mileage. */
    public const ALERT_WINDOW_KM = 200;

    protected $fillable = [
        'vehicle_id',
        'category',
        'interval_km',
        'alert_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => MaintenanceCategory::class,
            'interval_km' => 'integer',
            'alert_sent_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(VehicleMaintenanceCompletion::class);
    }

    /** Get (or lazily create, with the category's default interval) the schedule row for a vehicle+category. */
    public static function firstOrCreateFor(Vehicle $vehicle, MaintenanceCategory $category): self
    {
        return static::firstOrCreate(
            ['vehicle_id' => $vehicle->id, 'category' => $category],
            ['interval_km' => $category->defaultIntervalKm()],
        );
    }

    /** Baseline for the next-due calculation: the highest mileage recorded for a completed service of this category. */
    public function lastServiceMileage(): ?int
    {
        return $this->completions()->max('mileage');
    }

    /**
     * Next scheduled mileage for this category, or null if either the
     * interval isn't configured yet (e.g. transmission) or the category has
     * never had a completed service recorded — there is no baseline to
     * calculate from yet.
     */
    public function nextDueMileage(): ?int
    {
        $lastServiceMileage = $this->lastServiceMileage();

        if ($lastServiceMileage === null || $this->interval_km === null) {
            return null;
        }

        return $lastServiceMileage + $this->interval_km;
    }

    /**
     * 'unconfigured' — interval not yet defined, or no service history to calculate from.
     * 'unknown'      — schedule is configured, but the vehicle has no known current mileage yet.
     * 'overdue'      — past the next scheduled mileage.
     * 'due_soon'     — within the warning window (<= 200 km remaining).
     * 'ok'           — more than the warning window away.
     */
    public function alertStatus(?int $currentMileage = null): string
    {
        $currentMileage ??= $this->vehicle?->currentMileage();
        $nextDue = $this->nextDueMileage();

        if ($nextDue === null) {
            return 'unconfigured';
        }

        if ($currentMileage === null) {
            return 'unknown';
        }

        $remaining = $nextDue - $currentMileage;

        return match (true) {
            $remaining <= 0 => 'overdue',
            $remaining <= self::ALERT_WINDOW_KM => 'due_soon',
            default => 'ok',
        };
    }

    /** Km remaining until the next scheduled service, or null if it can't be calculated (see alertStatus()). */
    public function kmRemaining(?int $currentMileage = null): ?int
    {
        $nextDue = $this->nextDueMileage();
        $currentMileage ??= $this->vehicle?->currentMileage();

        if ($nextDue === null || $currentMileage === null) {
            return null;
        }

        return $nextDue - $currentMileage;
    }

    /**
     * Record a completed service for this category. This is the "reset":
     * it becomes the new baseline for the next due mileage, and clears any
     * previously sent alert so a new one can fire once the vehicle
     * approaches the next interval.
     */
    public function recordCompletion(int $mileage, string $serviceDate, int $createdBy, ?string $notes = null): VehicleMaintenanceCompletion
    {
        $completion = $this->completions()->create([
            'mileage' => $mileage,
            'service_date' => $serviceDate,
            'created_by' => $createdBy,
            'notes' => $notes,
        ]);

        $this->update(['alert_sent_at' => null]);

        return $completion;
    }

    /**
     * Send the maintenance alert email once per warning window: if the
     * vehicle has just entered (or already is in) the due_soon/overdue
     * status and no alert has been sent for it yet, email the configured
     * vehicle manager and mark it sent. Does nothing if already notified
     * for this window, if the vehicle isn't due yet, or if no manager
     * email is configured.
     */
    public function checkAndNotify(): void
    {
        if ($this->alert_sent_at !== null) {
            return;
        }

        if (! in_array($this->alertStatus(), ['due_soon', 'overdue'], true)) {
            return;
        }

        $managerEmail = config('vehicle.manager_email');

        if (! $managerEmail) {
            return;
        }

        Mail::to($managerEmail)->send(new MaintenanceAlertMail($this));

        $this->update(['alert_sent_at' => now()]);
    }
}
