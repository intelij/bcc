<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\RefuseSchedule;
use App\Models\ServiceInterruption;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Resident-facing pages load and behave. */
class PortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_for_resident_with_property(): void
    {
        $user = $this->makeUser();
        $account = $this->makePropertyWithAccount();
        $this->link($user, $account->property);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()->assertSee('Total outstanding');
    }

    public function test_dashboard_without_property_shows_link_prompt(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Link a property');
    }

    public function test_property_link_request_flow(): void
    {
        $user = $this->makeUser();
        $account = $this->makePropertyWithAccount();

        $this->actingAs($user)->post(route('properties.store'), [
            'account_number' => $account->account_number, 'relationship' => 'owner',
        ])->assertRedirect(route('properties.index'));

        $this->assertDatabaseHas('property_users', [
            'user_id' => $user->id, 'property_id' => $account->property_id, 'status' => 'pending',
        ]);
    }

    public function test_unknown_account_number_link_fails(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->post(route('properties.store'), [
            'account_number' => 'NOPE-000', 'relationship' => 'owner',
        ]);
        $this->assertDatabaseMissing('property_users', ['user_id' => $user->id]);
    }

    public function test_resident_submits_service_request(): void
    {
        $user = $this->makeUser();
        $account = $this->makePropertyWithAccount();
        $this->link($user, $account->property);

        $this->actingAs($user)->post(route('requests.store'), [
            'category' => 'water_leak', 'description' => 'Water leaking at boundary',
            'property_id' => $account->property_id, 'location_text' => 'Front gate',
        ])->assertRedirect();

        $this->assertDatabaseHas('service_requests', ['user_id' => $user->id, 'category' => 'water_leak']);
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'event' => 'SERVICE_REQUEST_CREATED']);
    }

    public function test_resident_cannot_view_anothers_service_request(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $req = ServiceRequest::create([
            'ticket_number' => 'SR-X', 'user_id' => $b->id, 'category' => 'other',
            'description' => 'x', 'status' => 'submitted',
        ]);

        $this->actingAs($a)->get(route('requests.show', $req))->assertForbidden();
    }

    public function test_announcements_list_only_published(): void
    {
        Announcement::create(['title' => 'Public news', 'slug' => 'pub1', 'body' => 'b', 'category' => 'general', 'status' => 'published', 'published_at' => now()]);
        Announcement::create(['title' => 'Draft secret', 'slug' => 'draft1', 'body' => 'b', 'category' => 'general', 'status' => 'draft']);

        $this->actingAs($this->makeUser())->get(route('announcements.index'))
            ->assertOk()->assertSee('Public news')->assertDontSee('Draft secret');
    }

    public function test_water_page_shows_targeted_interruptions(): void
    {
        $user = $this->makeUser();
        $account = $this->makePropertyWithAccount(['suburb' => 'Hillside']);
        $this->link($user, $account->property);

        ServiceInterruption::create(['title' => 'Hillside outage', 'service' => 'water', 'area' => 'Hillside', 'suburb' => 'Hillside', 'starts_at' => now(), 'status' => 'active']);
        ServiceInterruption::create(['title' => 'Nketa outage', 'service' => 'water', 'area' => 'Nketa', 'suburb' => 'Nketa', 'starts_at' => now(), 'status' => 'active']);

        $this->actingAs($user)->get(route('water.index'))
            ->assertOk()->assertSee('Hillside outage')->assertDontSee('Nketa outage');
    }

    public function test_refuse_page_loads(): void
    {
        RefuseSchedule::create(['area' => 'Suburbs', 'suburb' => 'Suburbs', 'collection_day' => 2, 'frequency' => 'weekly', 'status' => 'active']);

        $this->actingAs($this->makeUser())->get(route('refuse.index'))->assertOk()->assertSee('Suburbs');
    }

    public function test_statement_pdf_downloads(): void
    {
        $user = $this->makeUser();
        $account = $this->makePropertyWithAccount();
        $this->link($user, $account->property);

        $response = $this->actingAs($user)->get(route('statements.pdf', $account));
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}
