<?php

namespace App\Models;

use App\Enums\PhotoPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class VehiclePhoto extends Model
{
    protected $fillable = [
        'position',
        'disk',
        'path',
        'original_filename',
        'size',
        'mime_type',
    ];

    protected function casts(): array
    {
        return [
            'position' => PhotoPosition::class,
            'size' => 'integer',
        ];
    }

    public function photographable(): MorphTo
    {
        return $this->morphTo();
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
