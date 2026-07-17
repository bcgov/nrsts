<?php

namespace App\Listeners;

use App\Events\MinistryClaimSubmitted;
use App\Models\Claim;
use Illuminate\Support\Facades\Log;

class ProcessMinistrySubmittedClaim
{
    /**
     * Handle the event.
     */
    public function handle(MinistryClaimSubmitted $event): void
    {
        Log::info('handle ministry claim submitted');

        $claim_before_update = $event->claim;
        $status = $event->status;

        $claim = Claim::where('id', $claim_before_update->id)->with('offering')->first();
        $claim->process_feedback = null;

        // Enforce the offering seat limit when a claim is confirmed for EI.
        // Each committed claim consumes one seat, so the offering is full once
        // the number of committed claims exceeds the offering's seat count.
        if ($claim_before_update->claim_status === 'Submitted' && $status === 'EI Confirmed') {
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
