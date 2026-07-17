<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert the demographic self-identification fields on the claims table
     * from free-text strings to booleans so they can be captured as simple
     * yes/no checkboxes on the applicant form. Also give is_visible_minority
     * a default of false so all three behave consistently.
     */
    public function up(): void
    {
        foreach (['disability_status', 'indigenous_status'] as $column) {
            DB::statement("ALTER TABLE claims ALTER COLUMN {$column} DROP DEFAULT");
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

        DB::statement('ALTER TABLE claims ALTER COLUMN is_visible_minority SET DEFAULT false');
        DB::statement('UPDATE claims SET is_visible_minority = false WHERE is_visible_minority IS NULL');
        DB::statement('ALTER TABLE claims ALTER COLUMN is_visible_minority SET NOT NULL');
    }

    /**
     * Reverse the migration: restore the string columns and make
     * is_visible_minority nullable again.
     */
    public function down(): void
    {
        foreach (['disability_status', 'indigenous_status'] as $column) {
            DB::statement("ALTER TABLE claims ALTER COLUMN {$column} DROP NOT NULL");
            DB::statement("ALTER TABLE claims ALTER COLUMN {$column} DROP DEFAULT");
            DB::statement("ALTER TABLE claims ALTER COLUMN {$column} TYPE varchar(255) USING (
                CASE WHEN {$column} THEN 'Yes' ELSE 'No' END
            )");
        }

        DB::statement('ALTER TABLE claims ALTER COLUMN is_visible_minority DROP NOT NULL');
        DB::statement('ALTER TABLE claims ALTER COLUMN is_visible_minority DROP DEFAULT');
    }
};
