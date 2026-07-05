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
        if(!Schema::hasTable('email_template_langs'))
        {
            Schema::create('email_template_langs', function (Blueprint $table) {
                $table->id();
                $table->integer('parent_id')->default(0);
                $table->string('lang')->nullable();
                $table->string('subject')->nullable();
                $table->text('content')->nullable();
                $table->string('module_name')->nullable();
                $table->json('variables')->nullable();
                $table->timestamps();
            });
        }

         
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_template_langs');
    }
};
