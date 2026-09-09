<?php

namespace App\Support;

use App\Enums\ConditionComponent;
use App\Enums\ConditionStatus;
use App\Enums\EquipmentItem;
use App\Enums\PhotoPosition;
use App\Models\VehicleDelivery;
use App\Models\VehicleReception;

class VehicleComparisonBuilder
{
    public function build(VehicleReception $reception, VehicleDelivery $delivery): array
    {
        return [
            'mileage' => [
                'initial' => $reception->initial_mileage,
                'final' => $delivery->final_mileage,
                'delta' => $delivery->mileageDelta(),
            ],
            'fuel_level' => [
                'reception' => $reception->fuel_level,
                'delivery' => $delivery->fuel_level,
                'changed' => $reception->fuel_level !== $delivery->fuel_level,
                'decreased' => $delivery->fuel_level->weight() < $reception->fuel_level->weight(),
            ],
            'conditions' => $this->conditionDiffs($reception, $delivery),
            'equipment' => $this->equipmentDiffs($reception, $delivery),
            'condition_items' => $this->conditionItemDiffs($reception, $delivery),
            'documentation' => $this->documentationDiff($reception, $delivery),
            'photos' => $this->pairedPhotos($reception, $delivery),
            'new_anomaly' => $delivery->has_anomaly && ! $reception->has_anomaly,
        ];
    }

    private function conditionDiffs(VehicleReception $reception, VehicleDelivery $delivery): array
    {
        $diffs = [];

        foreach (VehicleReception::conditionFields() as $field) {
            $diffs[$field] = [
                'reception' => $reception->{$field},
                'delivery' => $delivery->{$field},
                'changed' => $reception->{$field} !== $delivery->{$field},
                'worsened' => $reception->{$field}->value === 'ok' && $delivery->{$field}->value === 'issue',
            ];
        }

        return $diffs;
    }

    /**
     * Diff every item of the 26-item "chequeo general" equipment checklist,
     * one row per item, so the comparison covers all of the questions asked
     * on the form and not just the five general condition fields.
     */
    private function equipmentDiffs(VehicleReception $reception, VehicleDelivery $delivery): array
    {
        $receptionChecks = $reception->equipmentChecks->keyBy(fn ($c) => $c->item->value);
        $deliveryChecks = $delivery->equipmentChecks->keyBy(fn ($c) => $c->item->value);

        return collect(EquipmentItem::cases())->map(function (EquipmentItem $item) use ($receptionChecks, $deliveryChecks) {
            $receptionCheck = $receptionChecks->get($item->value);
            $deliveryCheck = $deliveryChecks->get($item->value);

            $receptionPresent = $receptionCheck?->is_present;
            $deliveryPresent = $deliveryCheck?->is_present;

            return [
                'item' => $item->value,
                'label' => $item->label(),
                'reception_present' => $receptionPresent,
                'delivery_present' => $deliveryPresent,
                'changed' => $receptionPresent !== $deliveryPresent,
                'worsened' => $receptionPresent === true && $deliveryPresent === false,
                'reception_photo' => $receptionCheck?->photoUrl(),
                'delivery_photo' => $deliveryCheck?->photoUrl(),
                'reception_photo_disk' => $receptionCheck?->photo_disk,
                'reception_photo_path' => $receptionCheck?->photo_path,
                'delivery_photo_disk' => $deliveryCheck?->photo_disk,
                'delivery_photo_path' => $deliveryCheck?->photo_path,
            ];
        })->values()->all();
    }

    /**
     * Diff every component of the 12-item "estado general del vehículo"
     * checklist, one row per component, alongside its optional photos.
     */
    private function conditionItemDiffs(VehicleReception $reception, VehicleDelivery $delivery): array
    {
        $receptionItems = $reception->conditionItems->keyBy(fn ($c) => $c->item->value);
        $deliveryItems = $delivery->conditionItems->keyBy(fn ($c) => $c->item->value);

        return collect(ConditionComponent::cases())->map(function (ConditionComponent $component) use ($receptionItems, $deliveryItems) {
            $receptionItem = $receptionItems->get($component->value);
            $deliveryItem = $deliveryItems->get($component->value);

            $receptionStatus = $receptionItem?->status;
            $deliveryStatus = $deliveryItem?->status;

            return [
                'item' => $component->value,
                'label' => $component->label(),
                'reception' => $receptionStatus,
                'delivery' => $deliveryStatus,
                'changed' => $receptionStatus?->value !== $deliveryStatus?->value,
                'worsened' => $receptionStatus === ConditionStatus::Ok && $deliveryStatus === ConditionStatus::Issue,
                'reception_photo' => $receptionItem?->photoUrl(),
                'delivery_photo' => $deliveryItem?->photoUrl(),
                'reception_photo_disk' => $receptionItem?->photo_disk,
                'reception_photo_path' => $receptionItem?->photo_path,
                'delivery_photo_disk' => $deliveryItem?->photo_disk,
                'delivery_photo_path' => $deliveryItem?->photo_path,
            ];
        })->values()->all();
    }

    private function documentationDiff(VehicleReception $reception, VehicleDelivery $delivery): array
    {
        $receptionDocs = $reception->documentation->keyBy(fn ($doc) => $doc->document_type->value);
        $deliveryDocs = $delivery->documentation->keyBy(fn ($doc) => $doc->document_type->value);

        $types = $receptionDocs->keys()->merge($deliveryDocs->keys())->unique();

        return $types->map(function ($type) use ($receptionDocs, $deliveryDocs) {
            $receptionValid = $receptionDocs->get($type)?->is_valid;
            $deliveryValid = $deliveryDocs->has($type) ? $deliveryDocs->get($type)->is_valid : null;

            return [
                'document_type' => $type,
                'reception' => $receptionValid,
                'delivery' => $deliveryValid,
                // Only flag a real regression, not fields delivery doesn't track (e.g. driver's license).
                'changed' => $deliveryDocs->has($type) && $receptionValid !== $deliveryValid,
            ];
        })->values()->all();
    }

    /**
     * Group both stages' photos by position so the report can show them
     * side by side. Positions without a matching pair are still shown,
     * flagged as missing on one side, since a pair is never assumed.
     */
    private function pairedPhotos(VehicleReception $reception, VehicleDelivery $delivery): array
    {
        $receptionByPosition = $reception->photos->groupBy(fn ($p) => $p->position->value);
        $deliveryByPosition = $delivery->photos->groupBy(fn ($p) => $p->position->value);

        $positions = collect(PhotoPosition::cases())->map(fn ($p) => $p->value);

        return $positions->map(function ($position) use ($receptionByPosition, $deliveryByPosition) {
            return [
                'position' => $position,
                'reception_photos' => $receptionByPosition->get($position, collect())->values(),
                'delivery_photos' => $deliveryByPosition->get($position, collect())->values(),
            ];
        })
            ->filter(fn ($group) => $group['reception_photos']->isNotEmpty() || $group['delivery_photos']->isNotEmpty())
            ->values()
            ->all();
    }
}
