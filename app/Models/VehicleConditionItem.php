<?php

namespace App\Models;

use App\Enums\ConditionComponent;
use App\Enums\ConditionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class VehicleConditionItem extends Model
{
    protected $fillable = [
        'item',
        'status',
        'photo_disk',
        'photo_path',
        'photo_original_filename',
        'photo_size',
        'photo_mime_type',
    ];

    protected function casts(): array
    {
        return [
            'item' => ConditionComponent::class,
            'status' => ConditionStatus::class,
            'photo_size' => 'integer',
        ];
    }

    public function conditionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function hasPhoto(): bool
    {
        return ! empty($this->photo_path);
    }

    public function photoUrl(): ?string
    {
        return $this->hasPhoto()
            ? Storage::disk($this->photo_disk ?? 'public')->url($this->photo_path)
            : null;
    }
}
