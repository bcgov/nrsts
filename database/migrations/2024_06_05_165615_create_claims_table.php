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
        Schema::create('claims', function (Blueprint $table) {
            $table->id();

            $table->string('guid', 32)->index()->unique();

            $table->string('institution_guid', 32)->index();
            $table->foreign('institution_guid')->references('guid')->on('institutions')
                ->onDelete('cascade');
            $table->string('allocation_guid', 32)->index();
            $table->foreign('allocation_guid')->references('guid')->on('allocations')
                ->onDelete('cascade');
            $table->string('program_guid', 32)->index();
            $table->foreign('program_guid')->references('guid')->on('programs')
                ->onDelete('cascade');

            // Owning user (BCSC-authenticated student). student_guid is kept
            // temporarily and made nullable until the profile removal is completed.
            $table->string('user_guid', 32)->nullable()->after('id')->index();
            $table->foreign('user_guid')->references('guid')->on('users')->nullOnDelete();


            $table->bigInteger('sin')->nullable()->comment('Social insurance number.')->index();
            $table->string('first_name')->index();
            // 1. Name (last, middle, first) - first_name/last_name already exist
            $table->string('middle_name')->nullable();
            $table->string('last_name')->index();
            $table->string('dob');
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('claim_type')->nullable()->default('Program')
                ->comment('This used to be course-by-course. All new records will be set to Program');
            $table->string('course_name')->nullable()->comment('Historical. no need to be populated. ');

            $table->string('claim_status')->nullable();
            $table->string('outcome_status')->nullable();

            $table->float('registration_fee', 2)->default(0)->nullable();
            $table->float('materials_fee', 2)->default(0)->nullable();
            $table->float('program_fee', 2)->default(0)->nullable();
            $table->float('claim_percent', 2)->default(0)->nullable();

            $table->float('estimated_hold_amount', 2)->default(0)->nullable();
            $table->float('total_claim_amount', 2)->default(0)->nullable();

            $table->date('stable_enrolment_date')->nullable();
            $table->date('expected_stable_enrolment_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('psi_claim_request_date')->nullable();
            $table->date('reporting_completed_date')->nullable();
            $table->date('claimed_date')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->date('outcome_effective_date')->nullable();

            $table->boolean('fifty_two_week_affirmation')->default(true);
            $table->boolean('agreement_confirmed')->default(false);
            $table->boolean('registration_confirmed')->default(false);

            // 3. Address (address line 1, address line 2, city, province, postal code, country)
            // city + zip_code (postal code) already exist on the table and are reused.
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->nullable();

            // 5. Telephone number
            $table->string('telephone')->nullable();

            // 7. Gender identity [male, female, other, prefer not to report]
            $table->string('gender_identity')->nullable();

            // 8. Marital status [single, married or equivalent]
            $table->string('marital_status')->nullable();

            // 9. Number of dependants
            $table->unsignedSmallInteger('number_of_dependants')->nullable();

            // 10. Disability status
            $table->string('disability_status')->nullable();

            // 11. Indigenous identity [First Nations, Metis, Inuk]
            $table->string('indigenous_identity')->nullable();

            // 12. Immigration status [Canadian citizen, permanent resident, protected person]
            $table->string('immigration_status')->nullable();

            // 13. Immigration year
            $table->unsignedSmallInteger('immigration_year')->nullable();

            // 14. Visible minority status
            $table->string('visible_minority_status')->nullable();

            // 15. Highest level of education
            $table->string('highest_education_level')->nullable();

            // 16. Federal official language of choice
            $table->string('official_language_choice')->nullable();

            // 17. Federal official language of service
            $table->string('official_language_service')->nullable();

            // 18. Employment status at intake and exit
            $table->string('employment_status_intake')->nullable();
            $table->string('employment_status_exit')->nullable();

            // 19. Precarious employment data [casual, temporary, seasonal, permanent]
            $table->string('precarious_employment')->nullable();

            // 20-24. Intervention
            $table->string('intervention_name')->nullable();
            $table->string('intervention_code')->nullable();
            $table->date('intervention_start_date')->nullable();
            $table->date('intervention_end_date')->nullable();
            $table->string('intervention_outcome')->nullable();

            // 25. Credential/certificate earned
            $table->string('credential_earned')->nullable();

            // 26. NOC code and NAICS code
            $table->string('noc_code')->nullable();
            $table->string('naics_code')->nullable();

            // 27-30. Action plan
            $table->date('action_plan_start_date')->nullable();
            $table->date('action_plan_end_date')->nullable();
            $table->string('action_plan_outcome')->nullable();
            $table->date('action_plan_outcome_date')->nullable();

            // 31. Increase in literacy and essential skills
            $table->string('literacy_essential_skills_increase')->nullable();
        
            $table->string('claimed_by_user_guid')->nullable();
            $table->string('student_excel_guid')->nullable();
            $table->string('program_excel_guid')->nullable();
            $table->string('claim_excel_guid')->nullable();
            $table->text('process_feedback')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
