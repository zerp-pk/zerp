<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('helpdesk_replies'))
        {
            Schema::create('helpdesk_replies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->index();
                $table->text('message');
                $table->json('attachments')->nullable();
                $table->boolean('is_internal')->default(false);
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('ticket_id', 'helpdesk_replies_ticket_id_foreign')->references('id')->on('helpdesk_tickets')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_replies');
    }
};