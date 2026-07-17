<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the apprentice number the applicant must provide when submitting an
     * application. Nullable so incomplete drafts can still be saved; a value is
     * only enforced at submission time by the application request validation.
     */
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('apprentice_number')->nullable()->after('program_guid');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn('apprentice_number');
        });
    }
};
