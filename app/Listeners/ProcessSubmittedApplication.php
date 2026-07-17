<?php

namespace App\Listeners;

use App\Events\ApplicationSubmitted;
use App\Models\Claim;
use Illuminate\Support\Facades\Log;

class ProcessSubmittedApplication
{
    /**
     * Handle the event.
     */
    public function handle(ApplicationSubmitted $event): void
    {
        \Log::info('handle student ProcessSubmittedApplication');

        $claim_before_update = $event->application;
        $status = $event->status;
        $claim = Claim::where('id', $claim_before_update->id)->with('offering')->first();
        $claim->process_feedback = null;

        // If the claim submitted against an inactive offering stop there.
        if (! ($claim->offering && $claim->offering->active_status)) {
            $claim->claim_status = 'Draft';
            $claim->process_feedback = 'Claim submitted against an inactive offering';
        }

        $claim->save();
    }
}
