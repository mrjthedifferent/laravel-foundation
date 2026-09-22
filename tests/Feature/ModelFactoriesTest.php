<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\ActivityLog\Models\Device;
use Modules\ActivityLog\Models\EmailLog;
use Modules\ActivityLog\Models\SmsLog;
use Modules\ErrorReport\Models\ErrorReport;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Modules\Notification\Models\FirebaseToken;
use Modules\Notification\Models\Notification;
use Modules\Notification\Models\PushNotification;
use Modules\Otp\Models\OtpWhitelist;
use Modules\Otp\Models\VerificationCode;
use Modules\Settings\Models\Setting;
use Modules\User\Models\UserDocument;
use Modules\User\Models\UserLoginHistory;
use Mrj\Foundation\Models\Audit;
use Mrj\Foundation\Models\DeviceToken;
use Mrj\Foundation\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every Eloquent model the package ships has a factory that writes a valid row
 * against the real migrations — so `Model::factory()` works in project tests.
 */
class ModelFactoriesTest extends TestCase
{
    /**
     * @return array<string, array{class-string<Model>}>
     */
    public static function models(): array
    {
        return [
            'Audit' => [Audit::class],
            'DeviceToken' => [DeviceToken::class],
            'Device' => [Device::class],
            'EmailLog' => [EmailLog::class],
            'SmsLog' => [SmsLog::class],
            'ErrorReport' => [ErrorReport::class],
            'DownloadImportManager' => [DownloadImportManager::class],
            'FirebaseToken' => [FirebaseToken::class],
            'Notification' => [Notification::class],
            'PushNotification' => [PushNotification::class],
            'OtpWhitelist' => [OtpWhitelist::class],
            'VerificationCode' => [VerificationCode::class],
            'Setting' => [Setting::class],
            'UserDocument' => [UserDocument::class],
            'UserLoginHistory' => [UserLoginHistory::class],
        ];
    }

    /**
     * @param  class-string<Model>  $model
     */
    #[DataProvider('models')]
    public function test_the_factory_creates_persisted_rows(string $model): void
    {
        // Several rows, so a randomly chosen invalid value (e.g. an enum case
        // that doesn't exist) or a unique-column collision can't slip through.
        $created = $model::factory()->count(10)->create();

        $this->assertCount(10, $created);
        $first = $created->first();
        $this->assertInstanceOf(Model::class, $first);
        $this->assertSame(10, DB::table($first->getTable())->whereIn($first->getKeyName(), $created->modelKeys())->count());

        // Reading every attribute back runs the model's casts and accessors.
        foreach ($model::query()->whereKey($created->modelKeys())->get() as $row) {
            $this->assertIsArray($row->toArray());
        }
    }

    public function test_download_import_manager_states_cast_to_the_enums(): void
    {
        $record = DownloadImportManager::factory()->import()->completed('exports/x.xlsx')->create()->fresh();

        $this->assertSame(ImportType::Import, $record->type);
        $this->assertSame(ImportStatus::Completed, $record->status);
        $this->assertSame('exports/x.xlsx', $record->url);
        $this->assertInstanceOf(User::class, $record->user);
    }

    public function test_setting_states_store_values_through_the_type_aware_mutator(): void
    {
        $json = Setting::factory()->json(['a' => 1])->create()->fresh();
        $this->assertSame(['a' => 1], $json->value);

        $boolean = Setting::factory()->boolean()->create()->fresh();
        $this->assertTrue($boolean->value);

        $secret = Setting::factory()->encrypted('s3cret-value')->create();
        $raw = DB::table('settings')->where('id', $secret->id)->value('value');
        $this->assertNotSame('s3cret-value', $raw);
        $this->assertSame('s3cret-value', $secret->fresh()->value);

        $select = Setting::factory()->select(['x' => 'X', 'y' => 'Y'], 'y')->create()->fresh();
        $this->assertSame('y', $select->value);
        $this->assertSame(['x' => 'X', 'y' => 'Y'], $select->options);
    }

    public function test_log_factory_states(): void
    {
        $this->assertSame('sent', EmailLog::factory()->sent()->create()->status);
        $this->assertSame('failed', SmsLog::factory()->failed()->create()->status);
        $this->assertNull(Device::factory()->anonymous()->create()->user_id);
        $this->assertNull(Audit::factory()->bySystem()->create()->user_id);
    }
}
