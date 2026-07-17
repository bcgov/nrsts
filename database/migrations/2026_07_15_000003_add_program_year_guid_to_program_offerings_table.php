<?php

use App\Models\ProgramYear;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('program_offerings', function (Blueprint $table) {
            $table->string('program_year_guid', 32)->nullable()->after('program_guid')
                ->comment('The program year this offering belongs to (1-to-1).');
            $table->index('program_year_guid');
        });

        // Backfill existing offerings with the currently active program year so
        // the new association is populated for pre-existing data.
        $activeProgramYearGuid = ProgramYear::where('status', 'active')->value('guid');

        if ($activeProgramYearGuid) {
            DB::table('program_offerings')
                ->whereNull('program_year_guid')
                ->update(['program_year_guid' => $activeProgramYearGuid]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_offerings', function (Blueprint $table) {
            $table->dropIndex(['program_year_guid']);
            $table->dropColumn('program_year_guid');
        });
    }
};
