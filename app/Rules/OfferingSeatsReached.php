<?php

namespace App\Rules;

use App\Models\Claim;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OfferingSeatsReached implements ValidationRule
{
    protected $offering;

    public function __construct($offering)
    {
        $this->offering = $offering;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Only allow a claim to be submitted while the offering still has seats.
        // Each claim consumes one seat, so the limit is reached when the number of
        // active (seat-consuming) claims meets the offering's seat count.
        if (! $this->offering) {
            $fail('The selected program offering could not be found.');

            return;
        }

        $totalSeats = (int) $this->offering->total_seats;

        $activeClaims = Claim::whereIn('claim_status', ['Submitted', 'EI Confirmed', 'Training Started', 'Training Ended', 'Completed'])
            ->where('institution_guid', $this->offering->institution_guid)
            ->where('program_offering_guid', $this->offering->guid)
            ->count();

        if ($totalSeats > 0 && $activeClaims >= $totalSeats) {
            $fail('This program offering has reached its seat limit. Please contact the institution administrator.');
        }
    }
}
