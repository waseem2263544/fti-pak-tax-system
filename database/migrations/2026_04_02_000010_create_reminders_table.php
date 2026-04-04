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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->date('deadline_date');
            $table->enum('reminder_type', ['7_days', '3_days', '1_day', 'overdue'])->default('7_days');
            $table->boolean('email_sent')->default(false);
            $table->boolean('in_app_notified')->default(false);
            $table->boolean('escalated')->default(false);
            $table->foreignId('escalated_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index('deadline_date');
            $table->index('reminder_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
