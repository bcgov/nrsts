<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The demographic self-identification fields are now captured as dropdown
     * values sourced from PDEX (e.g. "Yes", "No", "Prefer not to answer"), so
     * convert them from booleans back to nullable strings.
     */
    public function up(): void
    {
        foreach (['disability_status', 'indigenous_status', 'is_visible_minority'] as $column) {
            DB::statement("ALTER TABLE claims ALTER COLUMN {$column} DROP NOT NULL");
            DB::statement("ALTER TABLE claims ALTER COLUMN {$column} DROP DEFAULT");
            DB::statement("ALTER TABLE claims ALTER COLUMN {$column} TYPE varchar(255) USING (
                CASE WHEN {$column} THEN 'Yes' ELSE 'No' END
            )");
        }
    }

    /**
     * Reverse the migration: coerce the strings back to booleans.
     */
    public function down(): void
    {
        foreach (['disability_status', 'indigenous_status', 'is_visible_minority'] as $column) {
            DB::statement("ALTER TABLE claims ALTER COLUMN {$column} TYPE boolean USING (
                CASE
                    WHEN {$column} IS NULL THEN false
                    WHEN lower({$column}) IN ('1', 'true', 't', 'yes', 'y') THEN true
                    ELSE false
                END
            )");
            DB::statement("ALTER TABLE claims ALTER COLUMN {$column} SET DEFAULT false");
            DB::statement("ALTER TABLE claims ALTER COLUMN {$column} SET NOT NULL");
        }
    }
};
