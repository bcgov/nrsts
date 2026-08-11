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
        Schema::table('claims', function (Blueprint $table) {
            $table->renameColumn('official_language_choice', 'spoken_language');
            $table->renameColumn('official_language_service', 'intervention_language_of_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->renameColumn('spoken_language', 'official_language_choice');
            $table->renameColumn('intervention_language_of_service', 'official_language_service');
        });
    }
};
