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
        Schema::table('program_offerings', function (Blueprint $table) {
            $table->string('intervention_language_of_service')->nullable()->after('location_name');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn('intervention_language_of_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_offerings', function (Blueprint $table) {
            $table->dropColumn('intervention_language_of_service');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->string('intervention_language_of_service')->nullable();
        });
    }
};
