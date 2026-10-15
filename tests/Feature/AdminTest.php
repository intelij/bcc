<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\PropertyUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_loads(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Municipal dashboard');
    }

    public function test_staff_can_approve_property_link(): void
    {
        $staff = $this->makeUser('customer_service');
        $resident = $this->makeUser();
        $account = $this->makePropertyWithAccount();
        $link = PropertyUser::create([
            'property_id' => $account->property_id, 'user_id' => $resident->id,
            'relationship' => 'owner', 'status' => 'pending',
        ]);

        $this->actingAs($staff)->post(route('admin.links.review', $link), ['action' => 'approve'])
            ->assertRedirect();

        $this->assertEquals('approved', $link->fresh()->status);
        $this->assertDatabaseHas('notifications', ['user_id' => $resident->id, 'event' => 'ACCOUNT_UPDATED']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'property.link_approved']);
    }

    public function test_content_admin_publishes_announcement(): void
    {
        $content = $this->makeUser('content_admin');
        $this->makeUser(); // recipient

        $a = Announcement::create([
            'title' => 'Test', 'slug' => 'test-x', 'body' => 'Body', 'category' => 'general',
            'status' => 'draft', 'created_by' => $content->id,
        ]);

        $this->actingAs($content)->post(route('admin.announcements.publish', $a))->assertRedirect();
        $this->assertEquals('published', $a->fresh()->status);
    }

    public function test_resident_cannot_publish_announcement(): void
    {
        $resident = $this->makeUser();
        $a = Announcement::create(['title' => 'T', 'slug' => 't-1', 'body' => 'b', 'category' => 'general', 'status' => 'draft']);

        $this->actingAs($resident)->post(route('admin.announcements.publish', $a))->assertForbidden();
    }

    public function test_finance_runs_bill_sync(): void
    {
        $finance = $this->makeUser('finance');
        // finance lacks integrations.manage → denied (admin only)
        $this->actingAs($finance)->post(route('admin.integrations.sync'), ['entity' => 'bills'])->assertForbidden();
    }

    public function test_admin_runs_bill_sync(): void
    {
        $admin = $this->makeUser('admin');
        $account = $this->makePropertyWithAccount();

        $this->actingAs($admin)->post(route('admin.integrations.sync'), ['entity' => 'bills'])->assertRedirect();

        $this->assertEquals(2, $account->bills()->count());
        $this->assertDatabaseHas('sync_jobs', ['entity' => 'bills', 'status' => 'completed']);
    }

    public function test_global_search_finds_account(): void
    {
        $admin = $this->makeUser('admin');
        $account = $this->makePropertyWithAccount();

        $this->actingAs($admin)->get('/admin/search?q='.$account->account_number)
            ->assertOk()->assertSee($account->account_number);
    }
}
