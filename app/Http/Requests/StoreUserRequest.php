<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create-users');
    }

    public function rules(): array
    {
        $typeRule = auth()->user()->type === 'superadmin' ? 'nullable' : 'required|exists:roles,id';
        
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'mobile_no' => 'nullable|string|regex:/^\+\d{1,3}\d{9,13}$/',
            'password' => ['required', 'confirmed', Password::defaults()],
            'type' => $typeRule,
            'is_enable_login' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => __('Role is required.'),
        ];
    }
}
