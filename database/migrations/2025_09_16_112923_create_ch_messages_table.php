<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('ch_messages'))
        {
            Schema::create('ch_messages', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('from_id');
                $table->bigInteger('to_id');
                $table->string('body', 5000)->nullable();
                $table->string('attachment')->nullable();
                $table->boolean('seen')->default(false);
                $table->boolean('deleted_by_sender')->default(false);
                $table->boolean('deleted_by_receiver')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ch_messages');
    }
};