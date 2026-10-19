<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\ConditionComponent;
use App\Enums\EquipmentItem;
use App\Enums\PhotoPosition;
use App\Models\VehiclePhoto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    /**
     * Store the signature against the given owner, if one was submitted:
     * either a hand-drawn canvas capture (a base64 PNG data URI) or an
     * uploaded PNG file. Exactly one is expected — validated upstream by
     * StoreVehicleReceptionRequest/StoreVehicleDeliveryRequest — and the
     * uploaded-file path reuses persistPhoto() so both methods end up
     * stored identically.
     */
    protected function storeSignature(Model $owner, Request $request): void
    {
        /** @var UploadedFile|null $file */
        $file = $request->file('signature_file');

        if ($file) {
            $this->persistPhoto($owner, $file, PhotoPosition::Signature);

            return;
        }

        $dataUri = $request->input('signature_data');

        if (! $dataUri || ! str_starts_with($dataUri, 'data:image/')) {
            return;
        }

        [$meta, $base64] = explode(',', $dataUri, 2) + [null, null];

        if (! $base64) {
            return;
        }

        $contents = base64_decode($base64, true);

        if ($contents === false) {
            return;
        }

        preg_match('/data:image\/(\w+);base64/', $meta, $matches);
        $extension = $matches[1] ?? 'png';
        $mimeType = "image/{$extension}";

        $disk = config('vehicle.photos_disk', 'public');
        $ownerType = str($owner::class)->afterLast('\\')->snake()->plural()->toString();
        $filename = 'firma-'.Str::random(10).'.'.$extension;
        $path = "vehicle-photos/{$ownerType}/{$owner->getKey()}/{$filename}";

        // Go through a real file + putFileAs() rather than put($path, $bytes):
        // the Cloudinary adapter hands raw strings to upload() as if they were
        // file paths/URLs, so binary contents can't be written that way.
        $tmp = tempnam(sys_get_temp_dir(), 'firma');

        try {
            file_put_contents($tmp, $contents);
            Storage::disk($disk)->putFileAs(dirname($path), new File($tmp), $filename);
        } finally {
            @unlink($tmp);
        }

        $owner->photos()->create([
            'position' => PhotoPosition::Signature,
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $filename,
            'size' => strlen($contents),
            'mime_type' => $mimeType,
        ]);
    }

    private function persistPhoto(Model $owner, UploadedFile $file, PhotoPosition $position): VehiclePhoto
    {
        $disk = config('vehicle.photos_disk', 'public');
        $ownerType = str($owner::class)->afterLast('\\')->snake()->plural()->toString();
        $path = $file->store("vehicle-photos/{$ownerType}/{$owner->getKey()}", $disk);

        return $owner->photos()->create([
            'position' => $position,
            'disk' => $disk,
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

    /**
     * Store one row per item asked on the equipment checklist
     * (EquipmentItem::formItems()). Items no longer asked are not written.
     *
     * @param  array<string, string>  $checks  ['herramientas' => '1', ...]
     */
    protected function storeEquipmentChecks(Model $owner, array $checks): void
    {
        foreach (EquipmentItem::formItems() as $item) {
            $owner->equipmentChecks()->create([
                'item' => $item,
                'is_present' => (bool) ($checks[$item->value] ?? false),
            ]);
        }
    }

    /**
     * Store one row per component still rated on the form
     * (ConditionComponent::formItems()).
     *
     * @param  array<string, string>  $items  ['pide_vias' => 'ok', ...]
     */
    protected function storeConditionItems(Model $owner, array $items): void
    {
        foreach (ConditionComponent::formItems() as $component) {
            $owner->conditionItems()->create([
                'item' => $component,
                'status' => $items[$component->value] ?? 'ok',
            ]);
        }
    }
}
