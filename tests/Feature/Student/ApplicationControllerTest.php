<?php

namespace Tests\Feature\Student;

use App\Events\ApplicationSubmitted;
use App\Models\Claim;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\ProgramYear;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable page existence check for Inertia testing
        config(['inertia.testing.ensure_pages_exist' => false]);

        // A BCSC-authenticated user is the applicant; there is no separate student profile.
        $this->user = User::factory()->create(['disabled' => false]);

        // The claim policy authorises applicants via the Student role.
        $studentRole = Role::firstOrCreate(['name' => Role::Student]);
        $this->user->roles()->attach($studentRole);
    }

    /**
     * Build the applicant profile payload captured on a claim.
     */
    private function profilePayload(): array
    {
        return [
            'social_insurance_number' => '932069073',
            'first_name'  => 'Alice',
            'last_name'   => 'Johnson',
            'date_of_birth' => '1990-05-15',
            'email_address' => 'alice.johnson@example.com',
            'city'        => 'Vancouver',
            'postal_code' => 'V0V0V0',
        ];
    }

    public function test_it_stores_a_new_application_and_dispatches_event(): void
    {
        Event::fake();

        $institution = Institution::factory()->create();
        $programYear = ProgramYear::factory()->create(['status' => 'active']);
        $program = Program::factory()->create([
            'institution_guid' => $institution->guid,
        ]);
        $offering = ProgramOffering::factory()->create([
            'institution_guid'  => $institution->guid,
            'program_guid'      => $program->guid,
            'program_year_guid' => $programYear->guid,
            'offering_status'   => 'approved',
        ]);

        $storeData = array_merge($this->profilePayload(), [
            'program_guid'           => $program->guid,
            'claim_status'           => 'Submitted',
        ]);

        $this->actingAs($this->user);
        $this->assertAuthenticatedAs($this->user);

        $response = $this->post(route('student.applications.store'), $storeData);

        // Expect a redirection to the student home route.
        $response->assertRedirect(route('student.home'));

        // Assert that the Claim record is created and owned by the user.
        $this->assertDatabaseHas('claims', [
            'institution_guid' => $storeData['institution_guid'],
            'program_guid'     => $storeData['program_guid'],
            'user_guid'        => $this->user->guid,
            'first_name'       => 'Alice',
            'last_name'        => 'Johnson',
        ]);

        // Assert that the ApplicationSubmitted event was dispatched.
        Event::assertDispatched(ApplicationSubmitted::class);
    }

    public function test_it_updates_an_existing_application_and_dispatches_event(): void
    {
        Event::fake();

        $institution = Institution::factory()->create();
        $programYear = ProgramYear::factory()->create(['status' => 'active']);
        $program = Program::factory()->create([
            'institution_guid' => $institution->guid,
        ]);
        $offering = ProgramOffering::factory()->create([
            'institution_guid'  => $institution->guid,
            'program_guid'      => $program->guid,
            'program_year_guid' => $programYear->guid,
            'offering_status'   => 'approved',
        ]);

        // Create a Claim record with initial valid data.
        $claim = Claim::factory()->create([
            'institution_guid'       => $institution->guid,
            'program_offering_guid'  => $offering->guid,
            'program_guid'           => $program->guid,
            'user_guid'              => $this->user->guid,
            'claim_status'           => 'Submitted',
        ]);

        $updateData = array_merge($this->profilePayload(), [
            'id'                     => $claim->id,
            'guid'                   => $claim->guid,
            'institution_guid'       => $institution->guid,
            'program_guid'           => $program->guid,
            'claim_status'           => 'Submitted',
        ]);

        $this->actingAs($this->user);
        $this->assertAuthenticatedAs($this->user);

        $response = $this->put(route('student.applications.update', $claim->id), $updateData);

        $response->assertRedirect(route('student.home'));
        $this->assertDatabaseHas('claims', [
            'id'                     => $claim->id,
            'guid'                   => $claim->guid,
            'claim_status'           => 'Submitted',
            'institution_guid'       => $institution->guid,
            'program_offering_guid'  => $offering->guid,
            'program_guid'           => $program->guid,
            'user_guid'              => $this->user->guid,
        ]);

        Event::assertDispatched(ApplicationSubmitted::class);
    }

    public function test_it_returns_inertia_dashboard_for_authenticated_user()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('student.home'));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Student::Dashboard')
                ->has('results')
                ->where('page', 'applications')
            );
    }

    public function test_it_fetches_paginated_applications_as_json()
    {
        $this->actingAs($this->user);

        // Create several claims owned by the user.
        Claim::factory()->count(30)->create(['user_guid' => $this->user->guid]);

        $response = $this->get(route('student.claims.fetchApplications'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'body' => [
                    'data',
                    'current_page',
                    'last_page',
                ],
            ]);
    }
}
