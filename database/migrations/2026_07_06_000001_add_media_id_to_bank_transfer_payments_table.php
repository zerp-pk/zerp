<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transfer_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_transfer_payments', 'media_id')) {
                $table->foreignId('media_id')->nullable()->after('attachment')
                    ->constrained('media')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_transfer_payments', function (Blueprint $table) {
            if (Schema::hasColumn('bank_transfer_payments', 'media_id')) {
                $table->dropConstrainedForeignId('media_id');
            }
        });
    }
};
