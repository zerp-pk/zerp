<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankTransferPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:approved,rejected,pending',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => __('Status is required.'),
            'status.string' => __('Status must be a valid string.'),
            'status.in' => __('Status must be approved, rejected, or pending.'),
        ];
    }
}