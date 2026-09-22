<?php

namespace Modules\ActivityLog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Modules\ActivityLog\Models\EmailLog;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Every outgoing email is logged (pending on MessageSending, sent on
 * MessageSent), and the log is browsable and deletable by permitted admins.
 */
class EmailLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);
        config(['mail.default' => 'array']);

        foreach (['View Email Log', 'Delete Email Log'] as $perm) {
            Permission::updateOrCreate(['name' => $perm, 'guard_name' => 'web'], ['module_name' => 'ActivityLog']);
        }

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->givePermissionTo(['View Email Log', 'Delete Email Log']);
    }

    public function test_sending_an_email_records_it_as_sent(): void
    {
        Mail::raw('Your report is ready.', function (Message $message): void {
            $message->to('reader@example.com', 'Reader')
                ->cc('copy@example.com')
                ->subject('Report ready');
        });

        $log = EmailLog::sole();
        $this->assertSame('reader@example.com', $log->to_email);
        $this->assertSame('Reader', $log->to_name);
        $this->assertSame(['copy@example.com'], $log->cc);
        $this->assertSame('Report ready', $log->subject);
        $this->assertSame('sent', $log->status);
        $this->assertNotNull($log->sent_at);
        $this->assertNotNull($log->uuid);
    }

    public function test_index_and_show_list_the_logs(): void
    {
        $log = EmailLog::factory()->sent()->create(['subject' => 'Weekly digest']);

        $this->actingAs($this->admin)
            ->get(route('admin.email-logs.index'))
            ->assertOk()
            ->assertSee('Weekly digest');

        $this->actingAs($this->admin)
            ->get(route('admin.email-logs.show', $log->id))
            ->assertOk()
            ->assertSee('Weekly digest');
    }

    public function test_viewing_requires_view_email_log(): void
    {
        $log = EmailLog::factory()->create();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.email-logs.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.email-logs.show', $log->id))->assertForbidden();
    }

    public function test_destroy_deletes_the_log(): void
    {
        $log = EmailLog::factory()->failed()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.email-logs.destroy', $log->id))
            ->assertRedirect(route('admin.email-logs.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($log);
    }

    public function test_destroy_requires_delete_email_log(): void
    {
        $log = EmailLog::factory()->create();
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->givePermissionTo('View Email Log');

        $this->actingAs($viewer)
            ->delete(route('admin.email-logs.destroy', $log->id))
            ->assertForbidden();

        $this->assertModelExists($log);
    }

    public function test_destroying_a_missing_log_is_a_404(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.email-logs.destroy', 999))
            ->assertNotFound();
    }
}
