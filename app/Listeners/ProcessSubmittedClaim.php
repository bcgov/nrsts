<?php

namespace App\Listeners;

use App\Events\ClaimSubmitted;
use App\Models\Claim;
use Illuminate\Support\Facades\Log;

class ProcessSubmittedClaim
{
    /**
     * Handle the event.
     */
    public function handle(ClaimSubmitted $event): void
    {
        Log::info('handle institution claim submitted');

        $claim_before_update = $event->claim;
        $status = $event->status;
        $claim = Claim::where('id', $claim_before_update->id)->with('offering.py', 'user')->first();
        $claim->process_feedback = null;

        // Allow the claim to be updated even when its offering's program year is
        // inactive, but only when the update is adding the outcome status.
        $allowedToUpdateClaimFromInactivePy = false;
        if ($claim_before_update->outcome_status === null && $claim->outcome_status !== null) {
            $allowedToUpdateClaimFromInactivePy = true;
        }

        if (!$allowedToUpdateClaimFromInactivePy && ! ($claim->offering && $claim->offering->active_status) && $status != 'Cancelled') {
            $claim->claim_status = 'Draft';
        }

        // Enforce the offering seat limit when an institution confirms EI for a
        // claim. Each confirmed claim consumes one seat, so the offering is full
        // once the number of committed claims exceeds the offering's seat count.
        elseif ($claim_before_update->claim_status === 'Submitted' && $status === 'EI Confirmed') {
            $totalSeats = (int) ($claim->offering->total_seats ?? 0);

            $committedCount = Claim::whereIn('claim_status', ['EI Confirmed', 'Training Started', 'Training Ended', 'Completed'])
                ->where('institution_guid', $claim->institution_guid)
                ->where('program_offering_guid', $claim->program_offering_guid)
                ->count();

            if ($totalSeats > 0 && $committedCount > $totalSeats) {
                $claim->process_feedback = 'Institution has reached the total claim amount';
                $claim->claim_status = 'Submitted';
            }
        }

        $claim->save();
    }
}
