<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A partnership firm being registered with the Registrar of Firms.
 *
 * One row holds everything the three filing documents need - Form A, the
 * affidavit and the partnership deed - so they are always generated from the
 * same names, CNICs, addresses and dates. Partners and witnesses vary in number
 * and are only ever read whole, so they live as JSON on the row.
 *
 * Production creates this table with public/migrate-firm-registrations.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('firm_name');
            $table->string('principal_place', 500);
            $table->string('other_places', 500)->nullable();
            $table->text('business_description');
            $table->date('deed_date');
            $table->date('commencement_date');
            $table->string('duration', 100)->default('AT WILL');
            $table->json('partners');
            $table->json('witnesses');
            $table->unsignedTinyInteger('managing_partner')->nullable();
            $table->text('managing_powers')->nullable();
            $table->decimal('stamp_top_cm', 5, 2)->default(11.25);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_registrations');
    }
};
