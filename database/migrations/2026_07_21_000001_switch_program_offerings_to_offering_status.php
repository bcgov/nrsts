<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Replace the boolean active_status on program_offerings with a
     * offering_status string (draft, submitted, approved, inactive).
     */
    public function up(): void
    {
        Schema::table('program_offerings', function (Blueprint $table) {
            $table->string('offering_status', 255)->default('draft')->after('total_seats')
                ->comment('offering lifecycle: draft, submitted, approved, inactive');
        });

        // Backfill: previously active offerings are approved, the rest inactive.
        DB::table('program_offerings')->where('active_status', true)->update(['offering_status' => 'approved']);
        DB::table('program_offerings')->where('active_status', false)->update(['offering_status' => 'inactive']);

        Schema::table('program_offerings', function (Blueprint $table) {
            $table->dropColumn('active_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_offerings', function (Blueprint $table) {
            $table->boolean('active_status')->default(true)->after('total_seats');
        });

        DB::table('program_offerings')->where('offering_status', 'approved')->update(['active_status' => true]);
        DB::table('program_offerings')->whereIn('offering_status', ['draft', 'submitted', 'inactive'])->update(['active_status' => false]);

        Schema::table('program_offerings', function (Blueprint $table) {
            $table->dropColumn('offering_status');
        });
    }
};
