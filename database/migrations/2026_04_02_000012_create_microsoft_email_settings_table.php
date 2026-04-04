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
        Schema::create('microsoft_email_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('client_id');
            $table->string('client_secret');
            $table->string('access_token');
            $table->string('refresh_token');
            $table->timestamp('token_expires_at')->nullable();
            $table->string('email_address');
            $table->string('fbr_sender_email')->default('no-reply@fbr.gov.pk');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('microsoft_email_settings');
    }
};
