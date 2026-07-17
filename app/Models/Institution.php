<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use HasFactory, SoftDeletes;

    // Append the computed attribute
    protected $appends = ['overallocation_flag', 'active_offerings_total_formatted'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['guid', 'name', 'active_status', 
        'bceid_business_guid', 'last_touch_by_user_guid', ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['last_touch_by_user_guid'];

    public function claims()
    {
        return $this->hasMany(Claim::class, 'institution_guid', 'guid')->orderBy('created_at');
    }

    public function activeClaims()
    {
        return $this->hasMany(Claim::class, 'institution_guid', 'guid')->where('claim_status', '!=', 'draft')->orderBy('created_at');
    }

    /**
     * The program offerings delivered by this institution.
     */
    public function offerings()
    {
        return $this->hasMany(ProgramOffering::class, 'institution_guid', 'guid')->orderBy('offering_name');
    }

    /**
     * The active program offerings delivered by this institution.
     */
    public function activeOfferings()
    {
        return $this->hasMany(ProgramOffering::class, 'institution_guid', 'guid')
            ->where('active_status', true);
    }

    /**
     * The active programs offered by this institution (via program_offerings).
     */
    public function activePrograms()
    {
        return $this->belongsToMany(
            Program::class,
            'program_offerings',
            'institution_guid',
            'program_guid',
            'guid',
            'guid'
        )->where('programs.active_status', true)->distinct();
    }

    public function staff()
    {
        return $this->hasMany(InstitutionStaff::class, 'institution_guid', 'guid')->whereHas('user');
    }

    public function users()
    {
        return $this->hasManyThrough(User::class, InstitutionStaff::class,
            'institution_guid', 'guid', 'guid', 'user_guid');
    }

    /**
     * Scope a query to only include admin users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active_status', true);
    }

    // Define the accessor for the computed attribute
    public function getOverallocationFlagAttribute()
    {
        // Each Claimed claim consumes one seat of its offering. The institution
        // is over-allocated once the number of Claimed claims across its active
        // offerings exceeds the total seats offered by those offerings.
        // Use the query builder (not the dynamic property) so the relation is
        // never cached/serialized, which would recurse via ProgramOffering::$with.
        $activeOfferingGuids = $this->activeOfferings()->pluck('guid');
        if ($activeOfferingGuids->isEmpty()) {
            return false; // No active offerings, no overallocation.
        }

        $totalSeats = (int) $this->activeOfferings()->sum('total_seats');

        $claimedCount = $this->claims()
            ->whereIn('program_offering_guid', $activeOfferingGuids)
            ->where('claim_status', 'Claimed')
            ->count();

        return $totalSeats > 0 && $claimedCount > $totalSeats;
    }

    /**
     * Total dollar amount committed across this institution's active offerings.
     */
    public function getActiveOfferingsTotalFormattedAttribute()
    {
        return number_format((float) $this->activeOfferings()->sum('total_amount'), 0, '.', '');
    }
}
