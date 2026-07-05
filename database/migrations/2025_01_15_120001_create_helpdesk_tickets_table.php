<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('helpdesk_tickets'))
        {
            Schema::create('helpdesk_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_id')->unique();
                $table->string('title');
                $table->text('description');
                $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                $table->foreignId('category_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamp('resolved_at')->nullable();

                $table->foreign('category_id', 'helpdesk_tickets_category_id_foreign')->references('id')->on('helpdesk_categories')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_tickets');
    }
};
