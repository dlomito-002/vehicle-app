<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreVehicleMaintenanceCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'mileage' => ['required', 'integer', 'min:0'],
            'service_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
