<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClaimInstitution extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'claim_guid', 'institution_guid',
        'bceid_business_guid', 'name', 'active_status',
    ];

    protected $casts = [
        'active_status' => 'boolean',
    ];

    public function claim()
    {
        return $this->belongsTo(Claim::class, 'claim_guid', 'guid');
    }
}
