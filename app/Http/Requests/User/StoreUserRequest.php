<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();
        return $u && in_array($u->role, [User::ROLE_ADMIN, User::ROLE_SUPERVISOR], true);
    }

    public function rules(): array
    {
        // Admin can create supervisor or operator. Supervisor can only create operator.
        $allowedRoles = $this->user()->isAdmin()
            ? [User::ROLE_SUPERVISOR, User::ROLE_OPERATOR]
            : [User::ROLE_OPERATOR];

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in($allowedRoles)],
            'is_active' => ['boolean'],
        ];
    }
}
