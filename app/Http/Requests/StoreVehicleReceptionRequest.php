<?php

namespace App\Http\Requests;

use App\Enums\ConditionComponent;
use App\Enums\ConditionStatus;
use App\Enums\DocumentType;
use App\Enums\EquipmentItem;
use App\Enums\FuelLevel;
use App\Enums\FuelType;
use App\Enums\PhotoPosition;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreVehicleReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $rules = [
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],

            'received_by_name' => ['required', 'string', 'max:255'],

            'trip_reason' => ['required', 'string', 'max:500'],
            'location' => ['required', 'string', 'max:255'],

            'reception_date' => ['required', 'date'],
            'reception_time' => ['required', 'date_format:H:i'],
            'initial_mileage' => ['required', 'integer', 'min:0'],
            'washed' => ['required', 'boolean'],

            'fuel_level' => ['required', Rule::enum(FuelLevel::class)],
            'fuel_type' => ['required', Rule::enum(FuelType::class)],

            'has_anomaly' => ['required', 'boolean'],
            'anomaly_description' => ['required_if:has_anomaly,1', 'nullable', 'string', 'max:1000'],

            // Signature can be either drawn on the canvas (a base64 PNG data
            // URI) or uploaded as a standalone PNG file — exactly one of the
            // two is required. 'nullable' here is what fixes the previous
            // bug where an empty signature_data value always failed the
            // starts_with rule with a validation.starts_with error instead
            // of surfacing the real "signature is missing" message.
            'signature_data' => ['nullable', 'required_without:signature_file', 'string', 'starts_with:data:image/'],
            'signature_file' => ['nullable', 'required_without:signature_data', 'image', 'mimes:png', 'max:5120'],

            'documentation' => ['required', 'array'],

            'equipment_checks' => ['required', 'array'],
            'condition_items' => ['required', 'array'],

            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:10240', 'mimes:jpg,jpeg,png,webp'],

            'anomaly_photos' => ['required_if:has_anomaly,1', 'nullable', 'array'],
            'anomaly_photos.*' => ['image', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
        ];

        // One binary status field per shared condition (general condition,
        // windows/mirrors/lights, tires, dashboard, cleanliness).
        foreach (ConditionStatus::fieldLabels() as $field => $label) {
            $rules[$field] = ['required', Rule::enum(ConditionStatus::class)];
        }

        // Reception documentation: registration card, sticker, driver's license.
        foreach (DocumentType::forReception() as $docType) {
            $rules["documentation.{$docType->value}"] = ['required', 'boolean'];
        }

        // One optional photo per standard position (front/rear/right/left/dashboard/interior).
        foreach (PhotoPosition::standardPositions() as $position) {
            $rules["position_photos.{$position->value}"] = ['nullable', 'image', 'max:10240', 'mimes:jpg,jpeg,png,webp'];
        }

        // 26-item "chequeo general" equipment checklist, each Sí/No with an optional photo.
        foreach (EquipmentItem::cases() as $item) {
            $rules["equipment_checks.{$item->value}"] = ['required', 'boolean'];
            $rules["equipment_photos.{$item->value}"] = ['nullable', 'image', 'max:10240', 'mimes:jpg,jpeg,png,webp'];
        }

        // 12-component "estado general del vehículo" checklist, each rated with an optional photo.
        foreach (ConditionComponent::cases() as $component) {
            $rules["condition_items.{$component->value}"] = ['required', Rule::enum(ConditionStatus::class)];
            $rules["condition_photos.{$component->value}"] = ['nullable', 'image', 'max:10240', 'mimes:jpg,jpeg,png,webp'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $total = count($this->file('position_photos', []) ?? [])
                + count($this->file('anomaly_photos', []) ?? []);

            if ($total > 10) {
                $validator->errors()->add('photos', 'Puedes cargar un máximo de 10 fotografías por registro.');
            }

            if ($this->boolean('has_anomaly') && empty($this->file('anomaly_photos'))) {
                $validator->errors()->add('anomaly_photos', 'Adjunta al menos una fotografía de la anomalía reportada.');
            }

            // A vehicle can't be requested again until it's returned.
            $vehicle = $this->filled('vehicle_id') ? Vehicle::find($this->input('vehicle_id')) : null;

            if ($vehicle && ! $vehicle->isAvailable()) {
                $validator->errors()->add('vehicle_id', 'Este vehículo ya está en uso y no puede solicitarse hasta que sea devuelto.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'anomaly_description.required_if' => 'Describe el daño, falla o anomalía.',
            'anomaly_photos.required_if' => 'Adjunta al menos una fotografía de la anomalía reportada.',
            'signature_data.required_without' => 'Se requiere la firma de la persona que recibe el vehículo (dibujada o en PNG).',
            'signature_file.required_without' => 'Se requiere la firma de la persona que recibe el vehículo (dibujada o en PNG).',
            'signature_file.mimes' => 'La firma subida debe ser un archivo PNG.',
        ];
    }
}

