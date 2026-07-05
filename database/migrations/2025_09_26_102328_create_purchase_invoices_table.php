<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if(!Schema::hasTable('purchase_invoices'))
        {
            Schema::create('purchase_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number');
                $table->date('invoice_date');
                $table->date('due_date');
                $table->unsignedBigInteger('vendor_id');
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->decimal('paid_amount', 15, 2)->default(0);
                $table->decimal('debit_note_applied', 15, 2)->default(0);
                $table->decimal('balance_amount', 15, 2)->default(0);
                $table->enum('status', ['draft', 'posted','partial','paid', 'overdue'])->default('draft');
                $table->string('payment_terms')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('vendor_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

                $table->index(['status', 'invoice_date']);
                $table->index('vendor_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
