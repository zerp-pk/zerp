<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons')->ignore($this->coupon->id)],
            'discount' => 'required|numeric|min:0',
            'limit' => 'nullable|integer|min:1',
            'type' => 'required|in:percentage,flat,fixed',
            'minimum_spend' => 'nullable|numeric|min:0',
            'maximum_spend' => 'nullable|numeric|min:0',
            'limit_per_user' => 'nullable|integer|min:1',
            'expiry_date' => 'nullable|date|after:today',
            'included_module' => 'nullable|array',
            'excluded_module' => 'nullable|array',
            'status' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Coupon name is required.'),
            'name.string' => __('Coupon name must be a valid string.'),
            'name.max' => __('Coupon name must not exceed 255 characters.'),
            'description.string' => __('Description must be a valid string.'),
            'code.required' => __('Coupon code is required.'),
            'code.string' => __('Coupon code must be a valid string.'),
            'code.max' => __('Coupon code must not exceed 50 characters.'),
            'code.unique' => __('This coupon code already exists.'),
            'discount.required' => __('Discount amount is required.'),
            'discount.numeric' => __('Discount must be a valid number.'),
            'discount.min' => __('Discount must be at least 0.'),
            'limit.integer' => __('Usage limit must be a valid number.'),
            'limit.min' => __('Usage limit must be at least 1.'),
            'type.required' => __('Discount type is required.'),
            'type.in' => __('Discount type must be percentage, flat, or fixed.'),
            'minimum_spend.numeric' => __('Minimum spend must be a valid number.'),
            'minimum_spend.min' => __('Minimum spend must be at least 0.'),
            'maximum_spend.numeric' => __('Maximum spend must be a valid number.'),
            'maximum_spend.min' => __('Maximum spend must be at least 0.'),
            'limit_per_user.integer' => __('Limit per user must be a valid number.'),
            'limit_per_user.min' => __('Limit per user must be at least 1.'),
            'expiry_date.date' => __('Please enter a valid expiry date.'),
            'expiry_date.after' => __('Expiry date must be after today.'),
            'included_module.array' => __('Included modules must be a valid list.'),
            'excluded_module.array' => __('Excluded modules must be a valid list.'),
            'status.boolean' => __('Status must be true or false.'),
        ];
    }
}