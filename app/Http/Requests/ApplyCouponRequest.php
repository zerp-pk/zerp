<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon_code' => 'required|string|max:50',
            'total_amount' => 'required|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_code.required' => __('Coupon code is required.'),
            'coupon_code.string' => __('Coupon code must be a valid string.'),
            'coupon_code.max' => __('Coupon code must not exceed 50 characters.'),
            'total_amount.required' => __('Total amount is required.'),
            'total_amount.numeric' => __('Total amount must be a valid number.'),
            'total_amount.min' => __('Total amount must be at least 0.'),
        ];
    }
}