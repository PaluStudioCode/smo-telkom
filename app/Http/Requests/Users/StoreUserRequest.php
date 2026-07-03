<?php

namespace App\Http\Requests\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in([
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMIN_INPUTER,
                User::ROLE_ACCOUNT_MANAGER,
            ])],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
