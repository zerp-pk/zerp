<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('purchase_returns'))
        {
            Schema::create('purchase_returns', function (Blueprint $table) {
                $table->id();
                $table->string('return_number');
                $table->date('return_date');
                $table->unsignedBigInteger('vendor_id');
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->unsignedBigInteger('original_invoice_id');
                $table->enum('reason', ['defective', 'wrong_item', 'damaged', 'excess_quantity', 'other'])->default('defective');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->enum('status', ['draft', 'approved', 'completed', 'cancelled'])->default('draft');
                $table->text('notes')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('vendor_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('original_invoice_id')->references('id')->on('purchase_invoices')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

                $table->index(['status', 'return_date']);
                $table->index('vendor_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
