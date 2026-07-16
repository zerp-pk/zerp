<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sidebar layout: the order of the top-level items, and which are hidden.
 *
 * Two scopes share the table. `company` is the default the company admin sets for
 * everyone; `user` is an individual's override of it. A company admin has both,
 * hence the scope column, since both rows key on the same user_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('scope', ['user', 'company']);
            // Menu keys, in display order. Items absent from this list keep their
            // built-in order and follow - so a newly installed module still appears.
            $table->json('order')->nullable();
            // NOT `hidden`: Eloquent reserves $hidden for serialisation, and an
            // attribute of that name does not round-trip.
            $table->json('hidden_items')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_preferences');
    }
};
