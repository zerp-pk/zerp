<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modules a company has switched off for itself.
 *
 * Deliberately separate from user_active_modules, which doubles as the record of
 * which paid add-ons a company owns. Switching a module off must not delete the
 * entitlement, or turning it back on would mean buying it again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disabled_modules', function (Blueprint $table) {
            $table->id();
            // The company (a user of type `company`), i.e. creatorId().
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('module');
            $table->timestamps();

            $table->unique(['user_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disabled_modules');
    }
};
