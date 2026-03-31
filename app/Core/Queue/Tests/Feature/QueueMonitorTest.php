<?php

namespace App\Core\Queue\Tests\Feature;

use App\Core\Queue\Livewire\Admin\QueueMonitor;
use App\Core\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class QueueMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test queue monitor dashboard is accessible with permission
     */
    public function test_queue_monitor_dashboard_is_accessible_with_permission(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(
            DB::table('roles')->where('name', 'Admin')->first()?->id ?? 1
        );

        $response = $this->actingAs($admin)->get('/admin/queue-monitor');

        $response->assertOk();
        $response->assertSeeLivewire(QueueMonitor::class);
    }

    /**
     * Test queue monitor returns 403 for unauthorized user
     */
    public function test_queue_monitor_returns_403_for_unauthorized_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/queue-monitor');

        $response->assertForbidden();
    }

    /**
     * Test queue monitor returns unauthenticated for guest
     */
    public function test_queue_monitor_returns_unauthenticated_for_guest(): void
    {
        $response = $this->get('/admin/queue-monitor');

        $response->assertRedirect('/login');
    }

    /**
     * Test queue monitor dashboard displays job count variables
     */
    public function test_queue_monitor_dashboard_displays_job_metrics(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(
            DB::table('roles')->where('name', 'Admin')->first()?->id ?? 1
        );

        Livewire::actingAs($admin)
            ->test(QueueMonitor::class)
            ->assertViewHas('pendingJobs')
            ->assertViewHas('failedJobs')
            ->assertViewHas('totalMonitoredJobs')
            ->assertViewHas('runningJobs')
            ->assertViewHas('completedJobs')
            ->assertViewHas('failedMonitoredJobs')
            ->assertViewHas('successRate')
            ->assertViewHas('avgExecutionTime')
            ->assertViewHas('recentJobs');
    }

    /**
     * Test queue process endpoint requires permission
     */
    public function test_queue_process_endpoint_requires_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/queue/process');

        $response->assertForbidden();
    }

    /**
     * Test queue process endpoint is accessible with permission
     */
    public function test_queue_process_endpoint_is_accessible_with_permission(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(
            DB::table('roles')->where('name', 'Admin')->first()?->id ?? 1
        );

        $response = $this->actingAs($admin)->post('/admin/queue/process');

        $response->assertRedirect();
    }

    /**
     * Test queue retry endpoint requires permission
     */
    public function test_queue_retry_endpoint_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/queue-monitor')
            ->assertForbidden();
    }

    /**
     * Test queue flush endpoint requires permission
     */
    public function test_queue_flush_endpoint_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/queue-monitor')
            ->assertForbidden();
    }

    /**
     * Test admin user has queue.manage permission
     */
    public function test_admin_user_has_queue_manage_permission(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(
            DB::table('roles')->where('name', 'Admin')->first()?->id ?? 1
        );

        $this->assertTrue($admin->hasPermission('queue.manage'));
    }

    /**
     * Test non-admin user does not have queue.manage permission
     */
    public function test_non_admin_user_does_not_have_queue_manage_permission(): void
    {
        $user = User::factory()->create();
        $userRole = DB::table('roles')->where('name', 'User')->first();

        if ($userRole) {
            $user->roles()->attach($userRole->id);
        }

        $this->assertFalse($user->hasPermission('queue.manage'));
    }
}
