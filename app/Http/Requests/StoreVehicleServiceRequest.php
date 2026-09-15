<?php

namespace App\Http\Requests;

use App\Enums\ServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreVehicleServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'service_type' => ['required', Rule::enum(ServiceType::class)],
            'other_description' => ['required_if:service_type,other', 'nullable', 'string', 'max:255'],

            'service_date' => ['required', 'date'],
            'mileage_at_service' => ['nullable', 'integer', 'min:0'],

            'next_service_date' => ['nullable', 'date', 'after_or_equal:service_date'],
            'next_service_mileage' => ['nullable', 'integer', 'gt:mileage_at_service'],

            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('next_service_date') && ! $this->filled('next_service_mileage')) {
                $validator->errors()->add(
                    'next_service_date',
                    'Define al menos una próxima fecha o kilometraje de servicio para poder generar alertas.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'other_description.required_if' => 'Describe el tipo de servicio realizado.',
            'next_service_mileage.gt' => 'El próximo kilometraje debe ser mayor que el kilometraje del servicio actual.',
        ];
    }
}
