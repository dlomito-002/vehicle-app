<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\ValidatesUserEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use ValidatesUserEmail {
        prepareForValidation as normalizeEmail;
    }

    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeEmail();

        // An unchecked checkbox is not submitted at all, so normalize it to
        // an explicit false.
        $this->merge([
            'receives_notification_emails' => $this->boolean('receives_notification_emails'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $this->uniqueEmailRule()],
            'role' => ['required', Rule::enum(UserRole::class)],
            'receives_notification_emails' => ['required', 'boolean'],
        ];
    }
}
