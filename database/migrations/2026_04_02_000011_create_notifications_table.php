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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('set null');
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['reminder', 'fbr_notice', 'task', 'escalation', 'general'])->default('general');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->foreignId('related_fbr_notice_id')->nullable()->constrained('fbr_notices')->onDelete('set null');
            $table->foreignId('related_reminder_id')->nullable()->constrained('reminders')->onDelete('set null');
            $table->foreignId('related_task_id')->nullable()->constrained('tasks')->onDelete('set null');
            $table->timestamps();
            $table->index('user_id');
            $table->index('is_read');
            $table->index('type');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
