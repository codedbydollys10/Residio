<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterSocietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Society
            'society_name' => ['required', 'string', 'max:150'],
            'city'         => ['nullable', 'string', 'max:100'],
            'state'        => ['nullable', 'string', 'max:100'],
            'plan_id'      => ['nullable', 'integer', 'exists:subscription_plans,id'],

            // Admin
            'admin_name'   => ['required', 'string', 'max:120'],
            'email'        => ['required', 'email', 'max:150', 'unique:users,email'],
            'password'     => ['required', 'confirmed', 'min:8'],
            'terms'        => ['accepted'],
        ];
    }
}
