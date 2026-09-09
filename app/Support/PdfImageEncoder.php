<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Dompdf renders far more reliably from embedded base64 images than from
 * on-disk paths or URLs, so every photo shown in the comparison PDF is
 * converted through here first. Missing or unreadable files degrade to
 * null instead of breaking the whole report.
 */
class PdfImageEncoder
{
    public static function dataUri(?string $disk, ?string $path): ?string
    {
        if (! $disk || ! $path) {
            return null;
        }

        try {
            $storage = Storage::disk($disk);

            if (! $storage->exists($path)) {
                return null;
            }

            $contents = $storage->get($path);
            $mime = $storage->mimeType($path) ?? 'image/jpeg';

            return 'data:'.$mime.';base64,'.base64_encode($contents);
        } catch (Throwable) {
            return null;
        }
    }

    /** Convenience wrapper for models exposing a ->disk/->photo_disk + ->path/->photo_path pair. */
    public static function fromModel(mixed $model, string $diskAttr = 'disk', string $pathAttr = 'path'): ?string
    {
        if (! $model) {
            return null;
        }

        return self::dataUri($model->{$diskAttr} ?? 'public', $model->{$pathAttr} ?? null);
    }
}
