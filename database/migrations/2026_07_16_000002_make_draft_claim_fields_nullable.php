<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drafts may be saved incomplete (the store request marks these fields
     * "nullable" unless the claim is being submitted), but the columns were
     * created NOT NULL, causing a 23502 error when saving a partial draft.
     * Allow null so drafts can be persisted.
     */
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('institution_guid', 32)->nullable()->change();
            $table->string('program_guid', 32)->nullable()->change();
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('date_of_birth')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('institution_guid', 32)->nullable(false)->change();
            $table->string('program_guid', 32)->nullable(false)->change();
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->string('date_of_birth')->nullable(false)->change();
        });
    }
};
