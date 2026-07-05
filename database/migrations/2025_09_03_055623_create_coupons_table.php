<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->unique();
            $table->decimal('discount', 10, 2);
            $table->integer('limit')->nullable();
            $table->enum('type', ['percentage', 'flat', 'fixed']);
            $table->decimal('minimum_spend', 10, 2)->nullable();
            $table->decimal('maximum_spend', 10, 2)->nullable();
            $table->integer('limit_per_user')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->json('included_module')->nullable();
            $table->json('excluded_module')->nullable();
            $table->boolean('status')->default(true);
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['status', 'expiry_date']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};