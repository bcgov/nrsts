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
        // Credential/Certificate Earned now stores Yes / No / Not applicable.
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn('credential_certificate_earned');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->string('credential_certificate_earned')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn('credential_certificate_earned');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->boolean('credential_certificate_earned')->nullable();
        });
    }
};
