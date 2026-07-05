<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_date' => 'required|date',
            'vendor_id' => 'required|integer|exists:users,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'original_invoice_id' => 'required|integer|exists:purchase_invoices,id',
            'reason' => 'required|in:defective,wrong_item,damaged,excess_quantity,other',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.original_invoice_item_id' => 'required|integer|min:1',
            'items.*.return_quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string'
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_id.exists' => __('Selected vendor does not exist.'),
            'items.required' => __('At least one item is required.'),
            'items.*.product_id.min' => __('Please select a product for each item.'),
            'items.*.return_quantity.min' => __('Return quantity must be at least 1.'),
            'items.*.unit_price.min' => __('Unit price must be 0 or greater.')
        ];
    }
}