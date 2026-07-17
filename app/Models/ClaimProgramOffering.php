<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClaimProgramOffering extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'claim_guid', 'program_offering_guid', 'institution_guid', 'program_guid',
        'offering_name', 'offering_description', 'study_start_date', 'study_end_date',
        'location_name', 'active_status', 'created_by_guid', 'updated_by_guid',
        'intervention_start_date', 'intervention_end_date',
        'action_plan_start_date', 'action_plan_end_date',
        'action_plan_outcome', 'action_plan_outcome_date',
    ];

    protected $casts = [
        'study_start_date' => 'date',
        'study_end_date' => 'date',
        'active_status' => 'boolean',
        'intervention_start_date' => 'date',
        'intervention_end_date' => 'date',
        'action_plan_start_date' => 'date',
        'action_plan_end_date' => 'date',
        'action_plan_outcome_date' => 'date',
    ];

    public function claim()
    {
        return $this->belongsTo(Claim::class, 'claim_guid', 'guid');
    }
}
