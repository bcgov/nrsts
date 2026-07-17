<?php

namespace Database\Factories;

use App\Models\Claim;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClaimFactory extends Factory
{
    protected $model = Claim::class;

    public function definition()
    {
        // Generate a 32-character GUID by removing dashes from a UUID.
        $guid = str_replace('-', '', (string) Str::uuid());

        return [
            'guid'                    => $guid,
            'institution_guid'        => function () {
                return Institution::factory()->create()->guid;
            },
            'program_offering_guid'        => function () {
                return ProgramOffering::factory()->create()->guid;
            },
            'user_guid'        => function () {
                return User::factory()->create()->guid;
            },
            'program_guid'        => function () {
                return Program::factory()->create()->guid;
            },
            'social_insurance_number' => $this->faker->optional()->numberBetween(100000000, 999999999),
            'first_name'              => $this->faker->firstName,
            'last_name'               => $this->faker->lastName,
            'date_of_birth'           => $this->faker->date('Y-m-d'),
            'email_address'           => $this->faker->optional()->safeEmail,
            'city'                    => $this->faker->optional()->city,
            'postal_code'             => $this->faker->optional()->postcode,
            'claim_status'            => $this->faker->optional()->randomElement(['Pending', 'Approved', 'Rejected']),
            'outcome_status'          => $this->faker->optional()->randomElement(['Completed', 'InProgress']),
            'claimed_by_user_guid'    => $this->faker->optional()->uuid,
            'student_excel_guid'      => $this->faker->optional()->uuid,
            'program_excel_guid'      => $this->faker->optional()->uuid,
            'claim_excel_guid'        => $this->faker->optional()->uuid,
            'process_feedback'        => $this->faker->optional()->paragraph,
        ];
    }
}
