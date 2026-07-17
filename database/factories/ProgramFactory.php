<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition()
    {
        // Generate a 32-character GUID by removing dashes.
        $guid = str_replace('-', '', (string) Str::uuid());

        return [
            'guid'                              => $guid,
            'institution_guid'        => function () {
                return Institution::factory()->create()->guid;
            },
            'program_name'                      => $this->faker->sentence(3),
            'active_status'                     => $this->faker->boolean,
            'last_touch_by_user_guid' => $this->faker->optional()->randomElement([str_replace('-', '', (string)Str::uuid()), null]),
        ];
    }
}
