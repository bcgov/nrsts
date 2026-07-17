<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\ProgramYear;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProgramOfferingFactory extends Factory
{
    protected $model = ProgramOffering::class;

    public function definition()
    {
        // Generate a 32-character GUID by removing dashes from a UUID.
        $guid = str_replace('-', '', (string) Str::uuid());

        return [
            'guid'               => $guid,
            'institution_guid'   => function () {
                return Institution::factory()->create()->guid;
            },
            'program_guid'       => function () {
                return Program::factory()->create()->guid;
            },
            'program_year_guid'  => function () {
                return ProgramYear::factory()->create()->guid;
            },
            'offering_name'      => $this->faker->words(3, true),
            'offering_description' => $this->faker->optional()->sentence,
            'start_date'   => $this->faker->date('Y-m-d'),
            'end_date'     => $this->faker->date('Y-m-d'),
            'location_name'      => $this->faker->optional()->city,
            'total_amount'       => $this->faker->numberBetween(10000, 500000),
            'total_seats'        => $this->faker->numberBetween(1, 50),
            'active_status'      => true,
        ];
    }
}
