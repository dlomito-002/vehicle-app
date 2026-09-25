<?php

namespace App\Models;

use App\Enums\MaintenanceCategory;
use App\Mail\MaintenanceAlertMail;
use App\Support\NotificationRecipients;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * One row per vehicle + MaintenanceCategory (basic/major).
 * This coexists with the free-text VehicleService log — it doesn't replace
 * it — and tracks a fixed km interval per category, computing the next due
 * mileage from the last completed service of that category (never from the
 * vehicle's current mileage directly).
 */
class VehicleMaintenanceSchedule extends Model
{
    /** Vehicle enters the warning window this many km before the next due mileage. */
    public const ALERT_WINDOW_KM = 200;

    /**
     * Once alerted, the vehicle must cover at least this many km more (half
     * the warning window) before another updated alert is sent. Reaching
     * 'overdue' always alerts regardless.
     */
    public const ALERT_PROGRESS_KM = self::ALERT_WINDOW_KM / 2;

    protected $fillable = [
        'vehicle_id',
        'category',
        'interval_km',
        'alert_sent_at',
        'alert_mileage',
        'alert_status',
    ];

    protected function casts(): array
    {
        return [
            'category' => MaintenanceCategory::class,
            'interval_km' => 'integer',
            'alert_sent_at' => 'datetime',
            'alert_mileage' => 'integer',
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
     * interval isn't configured or the category has never had a completed
     * service recorded — there is no baseline to calculate from yet.
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

        $this->update(['alert_sent_at' => null, 'alert_mileage' => null, 'alert_status' => null]);

        return $completion;
    }

    /**
     * Whether an alert is warranted for the given mileage/status, given the
     * mileage of the last alert of this cycle (alert_mileage):
     *  - never alerted this cycle            -> yes (entered the warning window)
     *  - escalated from due_soon to overdue  -> yes
     *  - advanced >= ALERT_PROGRESS_KM since the last alert -> yes (updated alert)
     *  - same/lower/slightly higher mileage  -> no (duplicate)
     */
    public function needsAlert(int $currentMileage, string $status): bool
    {
        if (! in_array($status, ['due_soon', 'overdue'], true)) {
            return false;
        }

        if ($this->alert_mileage === null) {
            return true;
        }

        if ($status === 'overdue' && $this->alert_status !== 'overdue') {
            return true;
        }

        return $currentMileage - $this->alert_mileage >= self::ALERT_PROGRESS_KM;
    }

    /**
     * Send the maintenance alert email when the vehicle enters the warning
     * window and again each time it progresses ALERT_PROGRESS_KM further
     * toward (or past) the due mileage — see needsAlert(). The mileage of
     * the alert is claimed atomically before sending so retried/concurrent
     * requests can't double-send, and released if the mail fails so the
     * alert is retried on the next check. Does nothing if no recipient is
     * configured (see NotificationRecipients). Never throws.
     */
    public function checkAndNotify(): void
    {
        $currentMileage = $this->vehicle?->currentMileage();
        $status = $this->alertStatus($currentMileage);

        if ($currentMileage === null || ! $this->needsAlert($currentMileage, $status)) {
            return;
        }

        $recipients = NotificationRecipients::emails();

        if ($recipients === []) {
            return;
        }

        $previous = [$this->alert_mileage, $this->alert_status, $this->alert_sent_at];

        $claimed = static::whereKey($this->id)
            ->where(fn ($q) => $this->alert_mileage === null
                ? $q->whereNull('alert_mileage')
                : $q->where('alert_mileage', $this->alert_mileage))
            ->update(['alert_mileage' => $currentMileage, 'alert_status' => $status, 'alert_sent_at' => now()]);

        if ($claimed === 0) {
            return; // another request already sent this alert
        }

        $this->forceFill(['alert_mileage' => $currentMileage, 'alert_status' => $status, 'alert_sent_at' => now()])->syncOriginal();

        try {
            Mail::to($recipients)->send(new MaintenanceAlertMail($this));
        } catch (Throwable $e) {
            [$this->alert_mileage, $this->alert_status, $this->alert_sent_at] = $previous;
            $this->update(['alert_mileage' => $previous[0], 'alert_status' => $previous[1], 'alert_sent_at' => $previous[2]]);

            Log::error('Maintenance alert email failed.', [
                'schedule_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Evaluate every category for a vehicle — used right after its mileage changes (reception/delivery). Never throws. */
    public static function checkAllFor(Vehicle $vehicle): void
    {
        try {
            foreach (MaintenanceCategory::cases() as $category) {
                $schedule = static::firstOrCreateFor($vehicle, $category);
                $schedule->setRelation('vehicle', $vehicle);
                $schedule->checkAndNotify();
            }
        } catch (Throwable $e) {
            Log::error('Maintenance alert check failed.', ['vehicle_id' => $vehicle->id, 'error' => $e->getMessage()]);
        }
    }
}
