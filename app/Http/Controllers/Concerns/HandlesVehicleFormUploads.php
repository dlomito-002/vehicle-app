<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\PhotoPosition;
use App\Models\VehicleDocumentation;
use App\Models\VehiclePhoto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Shared logic for the file-handling steps common to both the reception and
 * delivery forms, so the two controllers don't duplicate upload/storage code.
 */
trait HandlesVehicleFormUploads
{
    /**
     * Store the one-per-position photos plus any anomaly photos against the
     * given polymorphic owner (a VehicleReception or VehicleDelivery).
     */
    protected function storePhotos(Model $owner, Request $request): void
    {
        foreach (PhotoPosition::standardPositions() as $position) {
            /** @var UploadedFile|null $file */
            $file = $request->file("position_photos.{$position->value}");

            if ($file) {
                $this->persistPhoto($owner, $file, $position);
            }
        }

        foreach ((array) $request->file('anomaly_photos', []) as $file) {
            if ($file instanceof UploadedFile) {
                $this->persistPhoto($owner, $file, PhotoPosition::Anomaly);
            }
        }
    }

    private function persistPhoto(Model $owner, UploadedFile $file, PhotoPosition $position): VehiclePhoto
    {
        $ownerType = str($owner::class)->afterLast('\\')->snake()->plural()->toString();
        $path = $file->store("vehicle-photos/{$ownerType}/{$owner->getKey()}", 'public');

        return $owner->photos()->create([
            'position' => $position,
            'disk' => 'public',
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ]);
    }

    /**
     * Store one documentation row per requested document type against the
     * given polymorphic owner.
     *
     * @param  array<string, string>  $documentation  ['registration_card' => '1', ...]
     * @param  \App\Enums\DocumentType[]  $applicableTypes
     */
    protected function storeDocumentation(Model $owner, array $documentation, array $applicableTypes): void
    {
        foreach ($applicableTypes as $docType) {
            $owner->documentation()->create([
                'document_type' => $docType,
                'is_valid' => (bool) ($documentation[$docType->value] ?? false),
            ]);
        }
    }
}
