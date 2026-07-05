<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'number_of_users' => 'required',
            'storage_limit' => 'required|integer|min:0|max:100',
            'status' => 'boolean',
            'free_plan' => 'boolean',
            'modules' => 'nullable|array',
            'package_price_yearly' => 'required|numeric|min:0',
            'package_price_monthly' => 'required|numeric|min:0',
            'trial' => 'boolean',
            'trial_days' => 'required_if:trial,true|integer|min:0',
        ];
    }    
}