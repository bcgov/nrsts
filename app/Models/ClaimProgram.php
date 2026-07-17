<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClaimProgram extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'claim_guid', 'program_guid',
        'program_name', 'program_type', 'program_number', 'delivery_method', 'online_delivery_type',
        'credential_type', 'micro_credential_type', 'high_priority_industry', 'total_duration_hrs',
        'creditable', 'full_time', 'prov_funded_micro_cred', 'indigenous_related_learning',
        'diversity_inclusion_related_learning', 'active_status', 'start_date', 'end_date',
        'intervention_name', 'intervention_code', 'credential_earned', 'noc_code', 'naics_code',
        'literacy_essential_skills_increase',
    ];

    protected $casts = [
        'total_duration_hrs' => 'float',
        'creditable' => 'boolean',
        'full_time' => 'boolean',
        'prov_funded_micro_cred' => 'boolean',
        'indigenous_related_learning' => 'boolean',
        'diversity_inclusion_related_learning' => 'boolean',
        'active_status' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function claim()
    {
        return $this->belongsTo(Claim::class, 'claim_guid', 'guid');
    }
}
