<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterResidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'invite_code' => strtoupper(trim((string) $this->input('invite_code'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'invite_code' => [
                'required',
                'string',
                Rule::exists('societies', 'resident_invite_code')->where('status', 'active'),
            ],
            'type' => ['required', Rule::in(['owner', 'tenant'])],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'tower_id' => ['required', 'integer'],
            'flat_number' => ['required', 'string', 'max:50'],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
    }
}
