<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('sales_proposals'))
        {
            Schema::create('sales_proposals', function (Blueprint $table) {
                $table->id();
                $table->string('proposal_number');
                $table->date('proposal_date');
                $table->date('due_date');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->enum('status', ['draft', 'sent', 'accepted', 'rejected'])->default('draft');
                $table->boolean('converted_to_invoice')->default(false);
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->string('payment_terms')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('invoice_id')->references('id')->on('sales_invoices')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

                $table->index(['status', 'proposal_date']);
                $table->index('customer_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_proposals');
    }
};
