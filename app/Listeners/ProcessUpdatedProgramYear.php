<?php

namespace App\Listeners;

use App\Events\ProgramYearUpdated;
use App\Models\ProgramOffering;
use App\Models\ProgramYear;

class ProcessUpdatedProgramYear
{
    /**
     * Handle the event.
     * When a program year is set to active, all other program years are switched to
     * inactive. Program offerings follow their program year: offerings in the active
     * program year become active, offerings in every other program year become inactive.
     */
    public function handle(ProgramYearUpdated $event): void
    {
        $programYearId = $event->programYear->id;
        $status = $event->status;
        $programYear = ProgramYear::find($programYearId);

        if (! $programYear) {
            return;
        }

        // Deactivate other program years if the status is active
        if ($status === 'active') {
            // Deactivate all other program years
            ProgramYear::where('id', '!=', $programYearId)->update(['status' => 'inactive']);

            // Activate the offerings that belong to the now active program year.
            ProgramOffering::where('program_year_guid', $programYear->guid)
                ->update(['active_status' => true]);

            // Deactivate offerings that belong to the now inactive program years.
            // Offerings are linked to a program year via program_year_guid.
            ProgramOffering::whereNotNull('program_year_guid')
                ->where('program_year_guid', '!=', $programYear->guid)
                ->update(['active_status' => false]);
        }
    }
}
