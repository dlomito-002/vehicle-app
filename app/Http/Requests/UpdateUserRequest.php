<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\ValidatesUserEmail;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    use ValidatesUserEmail;

    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isAdmin();
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $this->uniqueEmailRule($user->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var User|null $target */
            $target = $this->route('user');

            // Same reasoning as UserPolicy::delete(): an admin must not be
            // able to drop their own admin role from this screen, which
            // would lock them out of user management with nobody able to
            // undo it.
            if ($target && $this->user()->is($target) && $this->input('role') !== $target->role->value) {
                $validator->errors()->add('role', 'No puedes cambiar tu propio rol.');
            }
        });
    }
}
