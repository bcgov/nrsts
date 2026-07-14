<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Claim extends Model
{
    use HasFactory, SoftDeletes;

    // Append the computed attribute
    protected $appends = ['py_admin_fee', 'claimed_by_name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['sin', 'first_name', 'last_name', 'dob', 'email', 'city', 'zip_code',
        'claim_type', 'course_name', 'claim_status', 'claimed_by_user_guid', 'claimed_date',
        'registration_fee', 'materials_fee', 'program_fee', 'claim_percent',
        'estimated_hold_amount', 'total_claim_amount',
        'stable_enrolment_date', 'expiry_date', 'psi_claim_request_date', 'reporting_completed_date',
        'fifty_two_week_affirmation', 'agreement_confirmed', 'registration_confirmed',
        'guid', 'institution_guid', 'allocation_guid', 'program_guid', 'expected_stable_enrolment_date',
        'expected_completion_date', 'outcome_effective_date', 'outcome_status', 'correction_amount', 'correction_comment',
        'funding_type',
        // Applicant profile captured on the claim (replaces the student profile)
        'user_guid', 'middle_name', 'address_line1', 'address_line2', 'province', 'country', 'telephone',
        'gender_identity', 'marital_status', 'number_of_dependants', 'disability_status',
        'indigenous_identity', 'immigration_status', 'immigration_year', 'visible_minority_status',
        'highest_education_level', 'official_language_choice', 'official_language_service',
        'employment_status_intake', 'employment_status_exit', 'precarious_employment',
        'intervention_name', 'intervention_code', 'intervention_start_date', 'intervention_end_date',
        'intervention_outcome', 'credential_earned', 'noc_code', 'naics_code',
        'action_plan_start_date', 'action_plan_end_date', 'action_plan_outcome', 'action_plan_outcome_date',
        'literacy_essential_skills_increase',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'intervention_start_date' => 'date',
        'intervention_end_date' => 'date',
        'action_plan_start_date' => 'date',
        'action_plan_end_date' => 'date',
        'action_plan_outcome_date' => 'date',
        'number_of_dependants' => 'integer',
        'immigration_year' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($claim) {
            // Snapshot the funding type from the program so each claim keeps its own copy.
            // Once a claim is Claimed the value is frozen, making historical claims immutable
            // even if the program's funding type is later changed.
            if (empty($claim->program_guid)) {
                return;
            }
            $isClaimed = $claim->claim_status === 'Claimed';
            if (!$isClaimed || empty($claim->funding_type)) {
                $program = Program::where('guid', $claim->program_guid)->first();
                $claim->funding_type = ($program && $program->funding_type)
                    ? $program->funding_type
                    : 'Gov. Priorities';
            }
        });

        static::updated(function ($claim) {
            $changes = $claim->getChanges();
            if (!empty($changes)) {

                // Capture the original attributes before the update
                $originalAttributes = $claim->getOriginal();

                // Remove the updated_at field if you don't want to track it
                unset($originalAttributes['updated_at']);

                // Get the attributes after the update
                $newAttributes = $claim->getAttributes();
                $newAttributes['updated_by_user_id'] = Auth::check() ? Auth::user()->id : null;

                // Remove the updated_at field if you don't want to track it
                unset($newAttributes['updated_at']);

                // Create the journal entry
                ClaimJournal::create([
                    'claim_id' => $claim->id,
                    'updated_fields' => array_keys($changes),
                    'old_values' => $originalAttributes,
                    'new_values' => $newAttributes,
                ]);
            }
        });
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_guid', 'guid');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_guid', 'guid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_guid', 'guid');
    }

    public function allocation()
    {
        return $this->belongsTo(Allocation::class, 'allocation_guid', 'guid');
    }

    // Define the accessor for the computed attribute
    public function getPyAdminFeeAttribute()
    {
        return $this->allocation->py->claim_percent;
    }

    public function getClaimedByNameAttribute()
    {
        if (is_null($this->claimed_by_user_guid)) {
            return null;
        }

        $user = User::where('guid', $this->claimed_by_user_guid)->first();
        if (!is_null($user)) {
            return $user->first_name.' '.$user->last_name;
        }
        return null;

    }
}
