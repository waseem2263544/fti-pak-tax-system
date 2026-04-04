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
        Schema::create('fbr_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('set null');
            $table->string('email_message_id')->unique();
            $table->string('subject');
            $table->text('body')->nullable();
            $table->text('notice_section')->nullable(); // Extracted from subject
            $table->string('tax_year')->nullable(); // e.g., "2024-25"
            $table->date('notice_date')->nullable();
            $table->date('email_received_at');
            $table->enum('status', ['new', 'reviewed', 'resolved', 'escalated'])->default('new');
            $table->string('sender_email');
            $table->text('raw_content')->nullable(); // Full email content
            $table->boolean('is_escalated')->default(false);
            $table->foreignId('escalated_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index('status');
            $table->index('client_id');
            $table->index('tax_year');
            $table->index('notice_section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fbr_notices');
    }
};
