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
        ];

        foreach ($categories as $type => $options) {
            foreach ($options as $option) {
                Util::firstOrCreate(
                    ['field_type' => $type, 'field_name' => $option],
                    ['active_flag' => true]
                );
            }
        }
    }
}
