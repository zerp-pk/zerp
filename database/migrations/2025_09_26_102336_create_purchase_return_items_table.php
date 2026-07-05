<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('purchase_return_items'))
        {
            Schema::create('purchase_return_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('return_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('original_invoice_item_id')->nullable();
                $table->integer('original_quantity');
                $table->integer('return_quantity');
                $table->decimal('unit_price', 15, 2);
                $table->decimal('discount_percentage', 5, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('tax_percentage', 5, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->string('reason')->nullable();
                $table->timestamps();

                $table->foreign('return_id')->references('id')->on('purchase_returns')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
