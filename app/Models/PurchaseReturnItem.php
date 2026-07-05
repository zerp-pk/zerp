<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Zerp\ProductService\Models\ProductServiceItem;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'return_id',
        'product_id',
        'original_invoice_item_id',
        'original_quantity',
        'return_quantity',
        'unit_price',
        'discount_percentage',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'total_amount',
        'reason'
    ];

    protected $casts = [
        'original_quantity' => 'integer',
        'return_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2'
    ];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'return_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductServiceItem::class, 'product_id');
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(PurchaseReturnItemTax::class, 'item_id');
    }

    public function originalInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoiceItem::class, 'original_invoice_item_id');
    }

    public function calculateAmounts()
    {
        $lineTotal = $this->return_quantity * $this->unit_price;
        $this->discount_amount = ($lineTotal * $this->discount_percentage) / 100;
        $afterDiscount = $lineTotal - $this->discount_amount;
        $this->tax_amount = ($afterDiscount * $this->tax_percentage) / 100;
        $this->total_amount = $afterDiscount + $this->tax_amount;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->calculateAmounts();
        });
    }
}