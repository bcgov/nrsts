<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the EI reference code captured by the institution when it confirms
     * Employment Insurance for an applicant (status "EI Confirmed").
     */
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('ei_reference_code')->nullable()->after('outcome_status');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn('ei_reference_code');
        });
    }
};
