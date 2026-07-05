<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('transfers'))
        {
            Schema::create('transfers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('from_warehouse')->nullable()->index();
                $table->unsignedBigInteger('to_warehouse')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->decimal('quantity', 15, 2);
                $table->date('date')->nullable();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('from_warehouse')->references('id')->on('warehouses')->onDelete('cascade');
                $table->foreign('to_warehouse')->references('id')->on('warehouses')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('product_service_items')->onDelete('cascade');
                $table->foreign('creator_id', 'transfers_creator_id_foreign')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by', 'transfers_created_by_foreign')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
