<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The financial/workflow columns removed from the claims table. The claim
     * amount is now derived from allocation.total_amount / total_seats, so the
     * per-claim fee, hold, correction and legacy date/consent columns are gone.
     *
     * @var array<int, string>
     */
    private array $columns = [
        'registration_fee',
        'materials_fee',
        'program_fee',
        'claim_percent',
        'estimated_hold_amount',
        'total_claim_amount',
        'correction_amount',
        'correction_comment',
        'stable_enrolment_date',
        'expected_stable_enrolment_date',
        'expiry_date',
        'psi_claim_request_date',
        'reporting_completed_date',
        'claimed_date',
        'expected_completion_date',
        'outcome_effective_date',
        'fifty_two_week_affirmation',
        'agreement_confirmed',
        'registration_confirmed',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                if (Schema::hasColumn('claims', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->float('registration_fee', 2)->default(0)->nullable();
            $table->float('materials_fee', 2)->default(0)->nullable();
            $table->float('program_fee', 2)->default(0)->nullable();
            $table->float('claim_percent', 2)->default(0)->nullable();
            $table->float('estimated_hold_amount', 2)->default(0)->nullable();
            $table->float('total_claim_amount', 2)->default(0)->nullable();
            $table->float('correction_amount', 2)->default(0)->nullable();
            $table->string('correction_comment')->nullable();
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
        });
    }
};
