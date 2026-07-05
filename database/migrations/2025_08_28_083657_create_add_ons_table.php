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
        if(!Schema::hasTable('add_ons'))
        {
            Schema::create('add_ons', function (Blueprint $table) {
                $table->id();
                $table->string('module');
                $table->string('name');
                $table->decimal('monthly_price', 8, 2);
                $table->decimal('yearly_price', 8, 2);
                $table->string('image')->nullable();
                $table->boolean('is_enable')->default(false);
                $table->boolean('for_admin')->default(false);
                $table->string('package_name');
                $table->integer('priority')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('add_ons');
    }
};
