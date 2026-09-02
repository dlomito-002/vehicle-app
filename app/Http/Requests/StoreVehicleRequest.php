<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'make' => ['required', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'license_plate' => ['required', 'string', 'max:20', 'unique:vehicles,license_plate'],
        ];
    }
}
