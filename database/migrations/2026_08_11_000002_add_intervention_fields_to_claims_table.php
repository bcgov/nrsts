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
            // Intervention / agreement details captured by Institution and Ministry
            // (not shown on the student application).
            $table->string('agreement_holder_name')->nullable();
            $table->string('agreement_number')->nullable();
            $table->string('action_plan_result_code')->nullable();
            $table->string('intervention_essential_skills')->nullable();
            $table->boolean('credential_certificate_earned')->nullable();
            $table->string('provincial_office_code')->nullable();
            $table->string('intervention_title')->nullable();
            $table->string('intervention_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'agreement_holder_name',
                'agreement_number',
                'action_plan_result_code',
                'intervention_essential_skills',
                'credential_certificate_earned',
                'provincial_office_code',
                'intervention_title',
                'intervention_code',
            ]);
        });
    }
};
