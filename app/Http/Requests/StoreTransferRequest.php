<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Zerp\ProductService\Models\WarehouseStock;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create-transfers');
    }

    public function rules(): array
    {
        return [
            'from_warehouse' => 'required|exists:warehouses,id',
            'to_warehouse' => 'required|exists:warehouses,id|different:from_warehouse',
            'product_id' => 'required|exists:product_service_items,id',
            'quantity' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $warehouseStock = WarehouseStock::where('warehouse_id', $this->from_warehouse)
                        ->where('product_id', $this->product_id)
                        ->first();

                    if (!$warehouseStock || $value > $warehouseStock->quantity) {
                        $availableQty = $warehouseStock ? $warehouseStock->quantity : 0;
                        $fail("Quantity cannot exceed available stock ({$availableQty}).");
                    }
                }
            ],
            'date' => 'required|date',
        ];
    }
}