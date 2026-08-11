<?php

namespace Database\Seeders;

use App\Models\Util;
use Illuminate\Database\Seeder;

class ClaimProfileUtilsSeeder extends Seeder
{
    /**
     * Seed the dropdown option lists used by the applicant profile captured on
     * each claim. Categories are keyed by `field_type`; each option is a row
     * whose `field_name` is the selectable value.
     */
    public function run(): void
    {
        $categories = [
            'Education Level' => [
                'Less than high school', 'High school diploma', 'Some post-secondary',
                'College diploma', 'Trade/apprenticeship', "Bachelor's degree", 'Graduate degree',
            ],
            'Official Language' => [
                'English', 'French',
            ],
            'Language Service' => [
                'English', 'French',
            ],
            'Employment Status' => [
                'Employed full-time', 'Employed part-time', 'Self-employed', 'Unemployed', 'Not in labour force',
            ],
            'Precarious Employment' => [
                'Yes', 'No',
            ],
            'Intervention Outcome' => [
                'Complete', 'Incomplete', 'Failed to Report', 'Cancelled', 'Rescheduled',
            ],
            'Credential Earned' => [
                'Certificate', 'Diploma', 'Degree', 'Micro-credential', 'None',
            ],
            'Action Plan Outcome' => [
                'Achieved', 'Partially achieved', 'Not achieved', 'In progress',
            ],
            'Literacy/Essential Skills' => [
                'Yes', 'No',
            ],
            'Regions' => [
                'BC Lower Mainland', 'BC Vancouver Island', 'BC Interior',
            ],
            // Fixed intervention/agreement values copied onto each claim on create.
            'Agreement Holder Name' => [
                'British Columbia',
            ],
            'Agreement Number' => [
                '999222011',
            ],
            'Intervention Title' => [
                'Apprenticeship',
            ],
            'Intervention Code' => [
                '214',
            ],
            // Outcome detail dropdowns required when an Intervention Outcome is set.
            'Action Plan Result Code' => [
                'Unemployed but available for work', 'Employed', 'Self-Employed',
                'Returned to School', 'Unspecified- participant could not be reach',
                'Not in labour force', 'Starting a new action plan',
            ],
            'Intervention Essential Skills' => [
                'Yes', 'No',
            ],
            'Credential Certificate Earned' => [
                'Yes', 'No', 'Not applicable',
            ],
        ];

        foreach ($categories as $type => $options) {
            foreach ($options as $option) {
                Util::firstOrCreate(
                    ['field_type' => $type, 'field_name' => $option],
                    ['active_flag' => true]
                );
            }
        }

        // Drop obsolete Intervention Outcome values no longer offered.
        Util::where('field_type', 'Intervention Outcome')
            ->whereNotIn('field_name', $categories['Intervention Outcome'])
            ->delete();

        // Ministry configuration value: the weekly support payment amount ($)
        // used to calculate program totals (# of seats x # of levels x weeks/level x amount).
        Util::firstOrCreate(
            ['field_type' => 'Support Payment Per Week', 'field_name' => '400'],
            [
                'field_description' => 'Weekly support payment amount ($) used to calculate program totals.',
                'active_flag' => true,
            ]
        );

        // EI Reference Codes keyed by region. The code for each region is stored in
        // field_description so it can be looked up from the region selected on a claim.
        $eiReferenceCodes = [
            'BC Lower Mainland' => '5910-02-2026-002027',
            'BC Vancouver Island' => '5988-02-2026-002027',
            'BC Interior' => '5986-02-2026-002027',
        ];

        foreach ($eiReferenceCodes as $region => $code) {
            Util::firstOrCreate(
                ['field_type' => 'EI Reference Codes', 'field_name' => $region],
                ['field_description' => $code, 'active_flag' => true]
            );
        }

        // Shared EI Reference Code validity window.
        Util::firstOrCreate(
            ['field_type' => 'EI Reference Codes Start Date', 'field_name' => '2026-07-26'],
            ['active_flag' => true]
        );

        Util::firstOrCreate(
            ['field_type' => 'EI Reference Codes Expiry Date', 'field_name' => '2027-07-25'],
            ['active_flag' => true]
        );
    }
}
