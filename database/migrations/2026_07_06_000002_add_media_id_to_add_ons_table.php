<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('add_ons', function (Blueprint $table) {
            if (!Schema::hasColumn('add_ons', 'media_id')) {
                $table->foreignId('media_id')->nullable()->after('image')
                    ->constrained('media')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('add_ons', function (Blueprint $table) {
            if (Schema::hasColumn('add_ons', 'media_id')) {
                $table->dropConstrainedForeignId('media_id');
            }
        });
    }
};
