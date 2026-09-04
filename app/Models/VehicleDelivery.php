<?php

namespace App\Models;

use App\Enums\ConditionStatus;
use App\Enums\FuelLevel;
use App\Enums\FuelType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleDelivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_reception_id',
        'vehicle_id',
        'created_by',
        'returned_by_name',
        'keys_received_by_name',
        'return_date',
        'return_time',
        'final_mileage',
        'fuel_level',
        'fuel_type',
        'washed',
        'general_condition',
        'windows_mirrors_lights',
        'tires_condition',
        'dashboard_indicators',
        'cleanliness',
        'has_anomaly',
        'anomaly_description',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'final_mileage' => 'integer',
            'fuel_level' => FuelLevel::class,
            'fuel_type' => FuelType::class,
            'washed' => 'boolean',
            'general_condition' => ConditionStatus::class,
            'windows_mirrors_lights' => ConditionStatus::class,
            'tires_condition' => ConditionStatus::class,
            'dashboard_indicators' => ConditionStatus::class,
            'cleanliness' => ConditionStatus::class,
            'has_anomaly' => 'boolean',
        ];
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(VehicleReception::class, 'vehicle_reception_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    /** Mileage driven during this checkout, computed from the linked reception. */
    public function mileageDelta(): int
    {
        return $this->final_mileage - $this->reception->initial_mileage;
    }
}

