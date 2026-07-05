<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if(!Schema::hasTable('ch_pinned'))
        {
            Schema::create('ch_pinned', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('pinned_id');
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('pinned_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['user_id', 'pinned_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('ch_pinned');
    }
};