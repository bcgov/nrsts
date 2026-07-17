<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename applicant profile columns on the claims table to match the PDEX
     * naming convention and drop legacy columns that are no longer used.
     */
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->renameColumn('sin', 'social_insurance_number');
            $table->renameColumn('dob', 'date_of_birth');
            $table->renameColumn('email', 'email_address');
            $table->renameColumn('telephone', 'phone_number');
            $table->renameColumn('zip_code', 'postal_code');
            $table->renameColumn('gender_identity', 'gender');
            $table->renameColumn('indigenous_identity', 'indigenous_status');
            $table->renameColumn('highest_education_level', 'highest_level_of_education');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'claim_type',
                'course_name',
                'student_excel_guid',
                'program_excel_guid',
                'claim_excel_guid',
            ]);
        });
    }

    /**
     * Reverse the migration: restore the original column names and re-add the
     * dropped legacy columns (structure only; historical data is not restored).
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('claim_type')->default('Program')->nullable();
            $table->string('course_name')->nullable()->comment('Historical. no need to be populated. ');
            $table->string('student_excel_guid')->nullable();
            $table->string('program_excel_guid')->nullable();
            $table->string('claim_excel_guid')->nullable();
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->renameColumn('social_insurance_number', 'sin');
            $table->renameColumn('date_of_birth', 'dob');
            $table->renameColumn('email_address', 'email');
            $table->renameColumn('phone_number', 'telephone');
            $table->renameColumn('postal_code', 'zip_code');
            $table->renameColumn('gender', 'gender_identity');
            $table->renameColumn('indigenous_status', 'indigenous_identity');
            $table->renameColumn('highest_level_of_education', 'highest_education_level');
        });
    }
};
