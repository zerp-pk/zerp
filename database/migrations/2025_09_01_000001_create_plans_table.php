<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('number_of_users')->default(1);
            $table->boolean('status')->default(true);
            $table->boolean('free_plan')->default(false);
            $table->json('modules')->nullable();
            $table->decimal('package_price_yearly', 10, 2)->default(0);
            $table->decimal('package_price_monthly', 10, 2)->default(0);
            $table->integer('storage_limit')->default(0); // in KB
            $table->boolean('trial')->default(false);
            $table->integer('trial_days')->default(0);
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamps();


            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};