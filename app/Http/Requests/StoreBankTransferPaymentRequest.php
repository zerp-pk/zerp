<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankTransferPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $plan = \App\Models\Plan::find($this->plan_id);
        $user_module = !empty($this->user_module_input) ? $this->user_module_input : '';
        $duration = !empty($this->time_period) ? $this->time_period : 'Month';
        $user_module_price = 0;
        if (!empty($user_module)) {
            $user_module_array = explode(',', $user_module);
            foreach ($user_module_array as $value) {
                $temp = ($duration == 'Year') ? ModulePriceByName($value)['yearly_price'] : ModulePriceByName($value)['monthly_price'];
                $user_module_price = $user_module_price + $temp;
            }
        }
        $plan_price = $plan ? (($duration == 'Year') ? $plan->package_price_yearly : $plan->package_price_monthly) : 0;
        $price = $plan_price + $user_module_price;
        if ($this->coupon_code) {
            $validation = applyCouponDiscount($this->coupon_code, $price, auth()->id());
            if ($validation['valid']) {
                $price = $validation['final_amount'];
            }
        }

        $receipt_rule = ($price > 0) ? 'required|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048';

        return [
            'time_period' => 'required|string|in:Month,Year',
            'payment_receipt' => $receipt_rule,
            'plan_id' => 'required|exists:plans,id',
            'coupon_code' => 'nullable|string|max:255',
            'user_module_input' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'time_period.required' => __('Time period is required.'),
            'payment_receipt.required' => __('Payment receipt is required.'),
            'plan_id.required' => __('Plan is required.'),
        ];
    }
}