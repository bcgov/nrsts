<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['guid', 'program_name', 'category', 'number_weeks', 'number_levels', 'active_status', 'last_touch_by_user_guid', 'funding_type',
        'intervention_name', 'intervention_code', 'credential_earned', 'noc_code', 'naics_code',
        'literacy_essential_skills_increase'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['last_touch_by_user_guid'];

    /**
     * Scope a query to only include admin users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsActive($query)
    {
        return $query->where('active_status', true);
    }

    /**
     * The offerings that belong to this program.
     */
    public function offerings(): HasMany
    {
        return $this->hasMany(ProgramOffering::class, 'program_guid', 'guid');
    }
}
