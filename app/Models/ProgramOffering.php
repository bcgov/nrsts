<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramOffering extends Model
{
    use HasFactory, SoftDeletes;

    // Append the computed attributes (seat/budget figures moved from allocations).
    protected $appends = ['total_amount_formatted', 'claimed'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['guid', 'institution_guid', 'program_guid', 'program_year_guid', 'offering_name', 'offering_description',
        'start_date', 'end_date', 'location_name', 'total_amount', 'total_seats',
        'intervention_start_date', 'intervention_end_date', 'action_plan_start_date', 'action_plan_end_date',
        'action_plan_outcome', 'action_plan_outcome_date', 'offering_status', 'created_by_guid', 'updated_by_guid'];

    /**
     * The relationships to always append to the model.
     *
     * @var array<int, string>
     */
    protected $with = ['institution'];

    /**
     * The program this offering belongs to.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_guid', 'guid');
    }

    /**
     * The program year this offering belongs to.
     */
    public function py(): BelongsTo
    {
        return $this->belongsTo(ProgramYear::class, 'program_year_guid', 'guid');
    }

    /**
     * The institution delivering this offering.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_guid', 'guid');
    }

    /**
     * The claims made against this offering.
     */
    public function claims()
    {
        return $this->hasMany(Claim::class, 'program_offering_guid', 'guid')->orderByDesc('created_at');
    }

    /**
     * Scope a query to only include active (approved) offerings.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsActive($query)
    {
        return $query->where('offering_status', 'approved');
    }

    /**
     * The claimed dollar value of this offering. Each committed claim consumes
     * one seat, so the claimed amount is the number of committed claims times
     * the per-seat amount (offering total / seat count).
     */
    public function getClaimedAttribute()
    {
        $claimedCount = $this->claims()
            ->whereIn('claim_status', ['EI Confirmed', 'Training Started', 'Training Ended', 'Completed'])
            ->count();

        $perSeat = $this->total_seats ? ($this->total_amount / $this->total_seats) : 0;

        return number_format($claimedCount * $perSeat, 0, '.', '');
    }

    public function getTotalAmountFormattedAttribute()
    {
        return number_format($this->total_amount, 0, '.', '');
    }
}
