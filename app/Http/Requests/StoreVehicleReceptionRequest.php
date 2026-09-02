<?php

namespace App\Http\Requests;

use App\Enums\ConditionStatus;
use App\Enums\DocumentType;
use App\Enums\FuelLevel;
use App\Enums\PhotoPosition;
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

            'reception_date' => ['required', 'date'],
            'reception_time' => ['required', 'date_format:H:i'],
            'initial_mileage' => ['required', 'integer', 'min:0'],

            'fuel_level' => ['required', Rule::enum(FuelLevel::class)],

            'has_anomaly' => ['required', 'boolean'],
            'anomaly_description' => ['required_if:has_anomaly,1', 'nullable', 'string', 'max:1000'],

            'documentation' => ['required', 'array'],

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
        });
    }

    public function messages(): array
    {
        return [
            'anomaly_description.required_if' => 'Describe el daño, falla o anomalía.',
            'anomaly_photos.required_if' => 'Adjunta al menos una fotografía de la anomalía reportada.',
        ];
    }
}
