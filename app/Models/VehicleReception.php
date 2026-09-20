<?php

namespace App\Models;

use App\Enums\ConditionStatus;
use App\Enums\FuelLevel;
use App\Enums\FuelType;
use App\Enums\PhotoPosition;
use App\Enums\ReceptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleReception extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'created_by',
        'received_by_name',
        'trip_reason',
        'location',
        'reception_date',
        'reception_time',
        'initial_mileage',
        'washed',
        'fuel_level',
        'fuel_type',
        'general_condition',
        'windows_mirrors_lights',
        'tires_condition',
        'dashboard_indicators',
        'cleanliness',
        'has_anomaly',
        'anomaly_description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'reception_date' => 'date',
            'initial_mileage' => 'integer',
            'washed' => 'boolean',
            'fuel_level' => FuelLevel::class,
            'fuel_type' => FuelType::class,
            'general_condition' => ConditionStatus::class,
            'windows_mirrors_lights' => ConditionStatus::class,
            'tires_condition' => ConditionStatus::class,
            'dashboard_indicators' => ConditionStatus::class,
            'cleanliness' => ConditionStatus::class,
            'has_anomaly' => 'boolean',
            'status' => ReceptionStatus::class,
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

    public function delivery(): HasOne
    {
        return $this->hasOne(VehicleDelivery::class);
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(VehiclePhoto::class, 'photographable');
    }

    public function documentation(): MorphMany
    {
        return $this->morphMany(VehicleDocumentation::class, 'documentable');
    }

    /** The 26-item "chequeo general" equipment checklist. */
    public function equipmentChecks(): MorphMany
    {
        return $this->morphMany(VehicleEquipmentCheck::class, 'checkable');
    }

    /** The 12-component "estado general del vehículo" detail checklist. */
    public function conditionItems(): MorphMany
    {
        return $this->morphMany(VehicleConditionItem::class, 'conditionable');
    }

    public function isOpen(): bool
    {
        return $this->status === ReceptionStatus::Open;
    }

    /** The hand-drawn signature captured on this form, if any. */
    public function signaturePhoto(): ?VehiclePhoto
    {
        return $this->photos->firstWhere('position', PhotoPosition::Signature);
    }

    /** The five shared binary inspection fields, in display order. */
    public static function conditionFields(): array
    {
        return array_keys(ConditionStatus::fieldLabels());
    }
}

