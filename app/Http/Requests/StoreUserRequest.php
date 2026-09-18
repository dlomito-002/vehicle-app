<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\ValidatesUserEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    use ValidatesUserEmail;

    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $this->uniqueEmailRule()],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }
}
