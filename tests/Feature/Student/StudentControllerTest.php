<?php

namespace Tests\Feature\Student;

use App\Models\Institution;
use App\Models\ProgramOffering;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable page existence check for Inertia testing
        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->user = User::factory()->create();
    }

    public function test_index_returns_dashboard_for_authenticated_user(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('student.home'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student::Dashboard')
                ->has('results')
            );
    }

    public function test_it_fetches_institutions_by_guid_or_all_when_none_provided()
    {
        $this->actingAs($this->user);

        $institution = Institution::factory()->create();
        ProgramOffering::factory()->create([
            'institution_guid' => $institution->guid,
            'offering_status'  => 'approved',
        ]);

        // Fetch a specific institution.
        $response = $this->get(route('student.claims.fetchInstitutions', ['institution' => $institution->guid]));
        $response->assertStatus(200)
            ->assertJsonFragment(['guid' => $institution->guid]);

        // Fetch all institutions.
        $responseAll = $this->get(route('student.claims.fetchInstitutions'));
        $responseAll->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'institutions',
            ]);
    }
}
