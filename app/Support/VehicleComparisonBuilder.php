<?php

namespace App\Support;

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
