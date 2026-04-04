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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('contact_no');
            $table->enum('status', ['Individual', 'AOP', 'Company']);
            $table->text('notes')->nullable();
            $table->string('fbr_username')->nullable()->default(null);
            $table->text('fbr_password')->nullable()->default(null); // Encrypted
            $table->string('it_pin_code')->nullable();
            $table->string('kpra_username')->nullable();
            $table->text('kpra_password')->nullable()->default(null); // Encrypted
            $table->string('kpra_pin')->nullable();
            $table->text('secp_password')->nullable()->default(null); // Encrypted
            $table->string('secp_pin')->nullable();
            $table->string('folder_link')->nullable();
            $table->timestamps();
            $table->index('name');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
