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
                'Completed', 'In progress', 'Not completed', 'Withdrawn',
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
        ];

        foreach ($categories as $type => $options) {
            foreach ($options as $option) {
                Util::firstOrCreate(
                    ['field_type' => $type, 'field_name' => $option],
                    ['active_flag' => true]
                );
            }
        }

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
