<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VehicleDocumentation extends Model
{
    protected $fillable = [
        'document_type',
        'is_valid',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'is_valid' => 'boolean',
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
