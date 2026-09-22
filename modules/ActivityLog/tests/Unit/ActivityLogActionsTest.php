<?php

namespace Modules\ActivityLog\Tests\Unit;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\ActivityLog\Actions\CreateSmsLogAction;
use Modules\ActivityLog\Actions\DeleteActivityLogAction;
use Modules\ActivityLog\Actions\DeleteSmsLogAction;
use Modules\ActivityLog\Actions\ExportActivityLogsAction;
use Modules\ActivityLog\Actions\MarkEmailLogSentAction;
use Modules\ActivityLog\Jobs\ActivityLogExportJob;
use Modules\ActivityLog\Models\EmailLog;
use Modules\ActivityLog\Models\SmsLog;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Mrj\Foundation\Models\Audit;
use RuntimeException;
use Tests\TestCase;

class ActivityLogActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sms_log_status_follows_the_gateway_result(): void
    {
        $action = app(CreateSmsLogAction::class);

        $sent = $action->execute('+8801712345678', 'Your code is 123456', ['message_id' => 'abc']);
        $refused = $action->execute('+8801712345678', 'Your code is 654321', false);

        $this->assertSame('success', $sent->status);
        $this->assertSame(['message_id' => 'abc'], json_decode((string) $sent->response, true));
        $this->assertSame('failed', $refused->status);
        $this->assertSame('false', $refused->response);
    }

    public function test_delete_sms_log_removes_the_row_and_404s_on_a_missing_one(): void
    {
        $log = SmsLog::factory()->create();

        app(DeleteSmsLogAction::class)->execute($log->id);
        $this->assertModelMissing($log);

        $this->expectException(ModelNotFoundException::class);
        app(DeleteSmsLogAction::class)->execute($log->id);
    }

    public function test_mark_email_log_sent_stamps_the_matching_log(): void
    {
        $log = EmailLog::factory()->create();
        $other = EmailLog::factory()->create();

        $result = app(MarkEmailLogSentAction::class)->execute((string) $log->uuid);

        $this->assertTrue($result?->is($log));
        $this->assertSame('sent', $log->fresh()->status);
        $this->assertNotNull($log->fresh()->sent_at);
        $this->assertSame('pending', $other->fresh()->status);
    }

    public function test_mark_email_log_sent_ignores_an_unknown_uuid(): void
    {
        $this->assertNull(app(MarkEmailLogSentAction::class)->execute('00000000-0000-0000-0000-000000000000'));
    }

    public function test_delete_activity_log_removes_the_audit(): void
    {
        $audit = Audit::factory()->create();

        app(DeleteActivityLogAction::class)->execute($audit->id);

        $this->assertModelMissing($audit);
    }

    public function test_delete_activity_log_wraps_a_missing_audit_in_a_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);

        app(DeleteActivityLogAction::class)->execute(999);
    }

    public function test_export_records_a_pending_download_and_queues_the_job(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        app(ExportActivityLogsAction::class)->execute($user, ['event' => 'updated']);

        $this->assertSame(1, DownloadImportManager::count());
        $this->assertDatabaseHas('download_import_managers', [
            'user_id' => $user->id,
            'type' => ImportType::Download->value,
            'status' => ImportStatus::Pending->value,
        ]);
        Queue::assertPushed(ActivityLogExportJob::class);
    }
}
