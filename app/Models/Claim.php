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
    protected $appends = ['claimed_by_name', 'total_claim_amount'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['social_insurance_number', 'first_name', 'last_name', 'date_of_birth', 'email_address', 'city', 'postal_code',
        'claim_status', 'claimed_by_user_guid',
        'guid', 'institution_guid', 'program_offering_guid', 'program_guid', 'apprentice_number',
        'outcome_status', 'ei_reference_code',
        // Applicant profile captured on the claim (replaces the student profile)
        'user_guid', 'middle_name', 'address_line1', 'address_line2', 'province', 'region', 'country', 'phone_number',
        'gender', 'marital_status', 'number_of_dependants', 'disability_status',
        'indigenous_status', 'indigenous_group', 'immigration_status', 'immigration_year',
        'visible_minority_status', 'racial_identity', 'is_visible_minority',
        'highest_level_of_education', 'official_language_choice', 'official_language_service',
        'employment_status_intake', 'employment_status_exit', 'precarious_employment',
        'intervention_outcome',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'number_of_dependants' => 'integer',
        'immigration_year' => 'integer',
        'is_visible_minority' => 'boolean',
        'disability_status' => 'boolean',
        'indigenous_status' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

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

    public function offering()
    {
        return $this->belongsTo(ProgramOffering::class, 'program_offering_guid', 'guid');
    }

    /**
     * Snapshot of the institution captured for this claim.
     */
    public function claimInstitution()
    {
        return $this->hasOne(ClaimInstitution::class, 'claim_guid', 'guid');
    }

    /**
     * Snapshot of the program captured for this claim.
     */
    public function claimProgram()
    {
        return $this->hasOne(ClaimProgram::class, 'claim_guid', 'guid');
    }

    /**
     * Snapshot of the program offering captured for this claim.
     */
    public function claimProgramOffering()
    {
        return $this->hasOne(ClaimProgramOffering::class, 'claim_guid', 'guid');
    }

    // Define the accessor for the computed attribute

    /**
     * The claim amount is derived from the offering: each claim consumes one
     * seat, so its value is the offering total divided by the seat count.
     */
    public function getTotalClaimAmountAttribute()
    {
        $offering = $this->offering;
        if (! $offering || empty($offering->total_seats)) {
            return 0;
        }

        return round($offering->total_amount / $offering->total_seats, 2);
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
