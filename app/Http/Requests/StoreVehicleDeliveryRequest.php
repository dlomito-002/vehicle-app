<?php

namespace App\Http\Requests;

use App\Enums\ConditionStatus;
use App\Enums\DocumentType;
use App\Enums\FuelLevel;
use App\Enums\PhotoPosition;
use App\Enums\ReceptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreVehicleDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        /** @var \App\Models\VehicleReception $reception */
        $reception = $this->route('reception');

        $rules = [
            'returned_by_name' => ['required', 'string', 'max:255'],
            'keys_received_by_name' => ['required', 'string', 'max:255'],

            'return_date' => ['required', 'date'],
            'return_time' => ['required', 'date_format:H:i'],
            'final_mileage' => ['required', 'integer', 'min:' . $reception->initial_mileage],

            'fuel_level' => ['required', Rule::enum(FuelLevel::class)],
            'washed' => ['required', 'boolean'],

            'has_anomaly' => ['required', 'boolean'],
            'anomaly_description' => ['required_if:has_anomaly,1', 'nullable', 'string', 'max:1000'],

            'documentation' => ['required', 'array'],

            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:10240', 'mimes:jpg,jpeg,png,webp'],

            'anomaly_photos' => ['required_if:has_anomaly,1', 'nullable', 'array'],
            'anomaly_photos.*' => ['image', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
        ];

        foreach (ConditionStatus::fieldLabels() as $field => $label) {
            $rules[$field] = ['required', Rule::enum(ConditionStatus::class)];
        }

        // Delivery documentation intentionally excludes driver's license.
        foreach (DocumentType::forDelivery() as $docType) {
            $rules["documentation.{$docType->value}"] = ['required', 'boolean'];
        }

        foreach (PhotoPosition::standardPositions() as $position) {
            $rules["position_photos.{$position->value}"] = ['nullable', 'image', 'max:10240', 'mimes:jpg,jpeg,png,webp'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var \App\Models\VehicleReception $reception */
            $reception = $this->route('reception');

            if (! $reception || $reception->status !== ReceptionStatus::Open) {
                $validator->errors()->add('reception', 'Esta recepción ya fue cerrada por otra devolución.');
            }

            $total = count($this->file('position_photos', []) ?? [])
                + count($this->file('anomaly_photos', []) ?? []);

            if ($total > 10) {
                $validator->errors()->add('photos', 'Puedes cargar un máximo de 10 fotografías por registro.');
            }

            if ($this->boolean('has_anomaly') && empty($this->file('anomaly_photos'))) {
                $validator->errors()->add('anomaly_photos', 'Adjunta al menos una fotografía de la anomalía reportada.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'final_mileage.min' => 'El kilometraje final no puede ser menor que el kilometraje inicial registrado.',
            'anomaly_description.required_if' => 'Describe el daño, falla o anomalía.',
            'anomaly_photos.required_if' => 'Adjunta al menos una fotografía de la anomalía reportada.',
        ];
    }
}
