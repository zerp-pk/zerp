<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ch_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('ch_messages', 'media_id')) {
                $table->foreignId('media_id')->nullable()->after('attachment')
                    ->constrained('media')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ch_messages', function (Blueprint $table) {
            if (Schema::hasColumn('ch_messages', 'media_id')) {
                $table->dropConstrainedForeignId('media_id');
            }
        });
    }
};
