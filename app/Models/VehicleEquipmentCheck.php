<?php

namespace App\Models;

use App\Enums\EquipmentItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class VehicleEquipmentCheck extends Model
{
    protected $fillable = [
        'item',
        'is_present',
        'photo_disk',
        'photo_path',
        'photo_original_filename',
        'photo_size',
        'photo_mime_type',
    ];

    protected function casts(): array
    {
        return [
            'item' => EquipmentItem::class,
            'is_present' => 'boolean',
            'photo_size' => 'integer',
        ];
    }

    public function checkable(): MorphTo
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
