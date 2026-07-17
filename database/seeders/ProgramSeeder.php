<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramYear;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProgramSeeder extends Seeder
{
    /**
     * Seed the programs and program_offerings tables with a small set of
     * realistic sample data. Re-running the seeder is safe: records are keyed
     * on a natural identifier so they are updated rather than duplicated.
     */
    public function run(): void
    {
        // Ensure at least one institution exists to own the offerings. Program
        // offerings reference an institution via institution_guid. Offerings are
        // distributed across every institution so each has sample data.
        $institutions = Institution::query()->get();

        if ($institutions->isEmpty()) {
            $institutions = collect([
                $this->makeInstitution('Northern Lights College'),
                $this->makeInstitution('Coastal Trades Institute'),
            ]);
        }

        // Program definitions keyed on program_name (used as the natural key so
        // the seeder is idempotent). Each program belongs to a category.
        $programs = [
            // Automotive & Transport
            ['program_name' => 'Asphalt Paving/Laydown Technician', 'category' => 'Automotive & Transport'],
            ['program_name' => 'Automotive Glass Technician', 'category' => 'Automotive & Transport'],
            ['program_name' => 'Diesel Engine Mechanic', 'category' => 'Automotive & Transport'],
            ['program_name' => 'Heavy Equipment Operator', 'category' => 'Automotive & Transport'],
            ['program_name' => 'Parts Technician', 'category' => 'Automotive & Transport'],

            // Aerospace & Marine
            ['program_name' => 'Aircraft Maintenance Technician', 'category' => 'Aerospace & Marine'],
            ['program_name' => 'Aircraft Structural Technician', 'category' => 'Aerospace & Marine'],
            ['program_name' => 'Marine Mechanical Technician', 'category' => 'Aerospace & Marine'],
            ['program_name' => 'Marine Service Technician', 'category' => 'Aerospace & Marine'],

            // Construction
            ['program_name' => 'Arborist Technician', 'category' => 'Construction'],
            ['program_name' => 'Climbing Arborist', 'category' => 'Construction'],
            ['program_name' => 'Field Arborist', 'category' => 'Construction'],
            ['program_name' => 'Utility Arborist', 'category' => 'Construction'],
            ['program_name' => 'Architectural Sheet Metal Worker', 'category' => 'Construction'],
            ['program_name' => 'Locksmith', 'category' => 'Construction'],
            ['program_name' => 'Piledriver and Bridgeworker', 'category' => 'Construction'],
            ['program_name' => 'Residential Building Maintenance Worker', 'category' => 'Construction'],

            // Electrical
            ['program_name' => 'Security Systems Technician', 'category' => 'Electrical'],

            // Elevating Devices
            ['program_name' => 'Boom Truck Operator – stiff boom, unlimited tonnage', 'category' => 'Elevating Devices'],
            ['program_name' => 'Boom Truck Operator – folding boom, unlimited tonnage', 'category' => 'Elevating Devices'],
            ['program_name' => 'Mobile Crane Operator (Hydraulic 80 tonnes and Under)', 'category' => 'Elevating Devices'],

            // Service and Hospitality
            ['program_name' => 'Professional Cook (Institutional Entry)', 'category' => 'Service and Hospitality'],
            ['program_name' => 'Professional Cook (Workplace Entry)', 'category' => 'Service and Hospitality'],
            ['program_name' => 'Embalmer', 'category' => 'Service and Hospitality'],
            ['program_name' => 'Embalmer and Funeral Director', 'category' => 'Service and Hospitality'],
            ['program_name' => 'Funeral Director', 'category' => 'Service and Hospitality'],
            ['program_name' => 'Meatcutter', 'category' => 'Service and Hospitality'],

            // Others
            ['program_name' => 'Saw Filer (forestry)', 'category' => 'Others'],
            ['program_name' => 'Water Well Driller', 'category' => 'Others'],
        ];

        $now = Carbon::now();

        // Offerings are linked to a program year via program_year_guid. Seed them
        // against the currently active program year when one exists.
        $programYearGuid = ProgramYear::where('status', 'active')->value('guid')
            ?? ProgramYear::orderByDesc('start_date')->value('guid');

        foreach ($programs as $index => $definition) {
            $program = Program::firstOrNew(['program_name' => $definition['program_name']]);

            // Only assign a guid on first creation so the stable identifier
            // referenced by program_offerings is never rewritten.
            if (! $program->exists) {
                $program->guid = str_replace('-', '', (string) Str::uuid());
            }

            $program->fill(array_merge([
                'active_status'                       => true,
                'literacy_essential_skills_increase'  => 'Yes',
            ], $definition));

            $program->save();

            // Attach one or two offerings per program, distributed across the
            // available institutions.
            $institution = $institutions[$index % $institutions->count()];

            $this->makeOffering($program, $institution, [
                'offering_name'        => $definition['program_name'].' — Fall Intake',
                'offering_description' => 'Fall cohort for '.$definition['program_name'].'.',
                'program_year_guid'    => $programYearGuid,
                'start_date'     => $now->copy()->addMonth()->toDateString(),
                'end_date'       => $now->copy()->addMonths(4)->toDateString(),
                'location_name'        => $institution->name.' Main Campus',
                'total_amount'         => 250000,
                'total_seats'          => 20,
            ], $now);

            // Give every second program a second (winter) offering.
            if ($index % 2 === 0) {
                $winterInstitution = $institutions[($index + 1) % $institutions->count()];

                $this->makeOffering($program, $winterInstitution, [
                    'offering_name'        => $definition['program_name'].' — Winter Intake',
                    'offering_description' => 'Winter cohort for '.$definition['program_name'].'.',
                    'program_year_guid'    => $programYearGuid,
                    'start_date'     => $now->copy()->addMonths(5)->toDateString(),
                    'end_date'       => $now->copy()->addMonths(8)->toDateString(),
                    'location_name'        => $winterInstitution->name.' Downtown Centre',
                    'total_amount'         => 150000,
                    'total_seats'          => 12,
                ], $now);
            }
        }
    }

    /**
     * Create a minimal institution record to own offerings when none exist.
     */
    private function makeInstitution(string $name): Institution
    {
        return Institution::firstOrCreate(
            ['name' => $name],
            [
                'guid'                => str_replace('-', '', (string) Str::uuid()),
                'bceid_business_guid' => str_replace('-', '', (string) Str::uuid()),
                'active_status'       => true,
            ]
        );
    }

    /**
     * Insert (or update) a program offering. Keyed on program + offering name so
     * repeated seeding does not create duplicates.
     */
    private function makeOffering(Program $program, Institution $institution, array $attributes, Carbon $now): void
    {
        $key = [
            'program_guid'  => $program->guid,
            'offering_name' => $attributes['offering_name'],
        ];

        $exists = DB::table('program_offerings')->where($key)->exists();

        // Only generate a guid on first insert so the offering keeps a stable
        // identifier across re-runs.
        $values = array_merge([
            'institution_guid' => $institution->guid,
            'active_status'    => true,
            'updated_at'       => $now,
        ], $attributes);

        if (! $exists) {
            $values['guid']       = str_replace('-', '', (string) Str::uuid());
            $values['created_at'] = $now;
        }

        DB::table('program_offerings')->updateOrInsert($key, $values);
    }
}
