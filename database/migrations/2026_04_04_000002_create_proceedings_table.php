<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proceedings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('stage', ['department', 'commissioner_appeals', 'tribunal'])->default('department');
            $table->string('case_number')->nullable();
            $table->string('tax_year')->nullable();
            $table->string('section')->nullable();
            $table->date('hearing_date')->nullable();
            $table->date('order_date')->nullable();
            $table->enum('status', ['pending', 'adjourned', 'decided', 'appealed'])->default('pending');
            $table->text('outcome')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['stage']);
            $table->index(['status']);
            $table->index(['hearing_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proceedings');
    }
};
