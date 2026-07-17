<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Relocate intervention / credential / action-plan fields off the claim and
     * onto the program and program offering definitions (and their claim-time
     * snapshots). The claim retains only precarious_employment and
     * intervention_outcome.
     */
    public function up(): void
    {
        // Program-level fields.
        Schema::table('programs', function (Blueprint $table) {
            $table->string('intervention_name')->nullable()->after('end_date');
            $table->string('intervention_code')->nullable()->after('intervention_name');
            $table->string('credential_earned')->nullable()->after('intervention_code');
            $table->string('noc_code')->nullable()->after('credential_earned');
            $table->string('naics_code')->nullable()->after('noc_code');
            $table->string('literacy_essential_skills_increase')->nullable()->after('naics_code');
        });

        // Program offering-level fields.
        Schema::table('program_offerings', function (Blueprint $table) {
            $table->date('intervention_start_date')->nullable()->after('location_name');
            $table->date('intervention_end_date')->nullable()->after('intervention_start_date');
            $table->date('action_plan_start_date')->nullable()->after('intervention_end_date');
            $table->date('action_plan_end_date')->nullable()->after('action_plan_start_date');
            $table->string('action_plan_outcome')->nullable()->after('action_plan_end_date');
            $table->date('action_plan_outcome_date')->nullable()->after('action_plan_outcome');
        });

        // Mirror into the program snapshot table.
        Schema::table('claim_programs', function (Blueprint $table) {
            $table->string('intervention_name')->nullable()->after('end_date');
            $table->string('intervention_code')->nullable()->after('intervention_name');
            $table->string('credential_earned')->nullable()->after('intervention_code');
            $table->string('noc_code')->nullable()->after('credential_earned');
            $table->string('naics_code')->nullable()->after('noc_code');
            $table->string('literacy_essential_skills_increase')->nullable()->after('naics_code');
        });

        // Mirror into the program offering snapshot table.
        Schema::table('claim_program_offerings', function (Blueprint $table) {
            $table->date('intervention_start_date')->nullable()->after('location_name');
            $table->date('intervention_end_date')->nullable()->after('intervention_start_date');
            $table->date('action_plan_start_date')->nullable()->after('intervention_end_date');
            $table->date('action_plan_end_date')->nullable()->after('action_plan_start_date');
            $table->string('action_plan_outcome')->nullable()->after('action_plan_end_date');
            $table->date('action_plan_outcome_date')->nullable()->after('action_plan_outcome');
        });

        // Remove the relocated fields from the claim.
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'intervention_name',
                'intervention_code',
                'intervention_start_date',
                'intervention_end_date',
                'credential_earned',
                'noc_code',
                'naics_code',
                'action_plan_start_date',
                'action_plan_end_date',
                'action_plan_outcome',
                'action_plan_outcome_date',
                'literacy_essential_skills_increase',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the relocated fields on the claim.
        Schema::table('claims', function (Blueprint $table) {
            $table->string('intervention_name')->nullable();
            $table->string('intervention_code')->nullable();
            $table->date('intervention_start_date')->nullable();
            $table->date('intervention_end_date')->nullable();
            $table->string('credential_earned')->nullable();
            $table->string('noc_code')->nullable();
            $table->string('naics_code')->nullable();
            $table->date('action_plan_start_date')->nullable();
            $table->date('action_plan_end_date')->nullable();
            $table->string('action_plan_outcome')->nullable();
            $table->date('action_plan_outcome_date')->nullable();
            $table->string('literacy_essential_skills_increase')->nullable();
        });

        Schema::table('claim_program_offerings', function (Blueprint $table) {
            $table->dropColumn([
                'intervention_start_date', 'intervention_end_date',
                'action_plan_start_date', 'action_plan_end_date',
                'action_plan_outcome', 'action_plan_outcome_date',
            ]);
        });

        Schema::table('claim_programs', function (Blueprint $table) {
            $table->dropColumn([
                'intervention_name', 'intervention_code', 'credential_earned',
                'noc_code', 'naics_code', 'literacy_essential_skills_increase',
            ]);
        });

        Schema::table('program_offerings', function (Blueprint $table) {
            $table->dropColumn([
                'intervention_start_date', 'intervention_end_date',
                'action_plan_start_date', 'action_plan_end_date',
                'action_plan_outcome', 'action_plan_outcome_date',
            ]);
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn([
                'intervention_name', 'intervention_code', 'credential_earned',
                'noc_code', 'naics_code', 'literacy_essential_skills_increase',
            ]);
        });
    }
};
