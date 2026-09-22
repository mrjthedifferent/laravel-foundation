<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Modules\ActivityLog\Models\Device;
use Modules\Notification\Models\FirebaseToken;
use Modules\User\Models\UserDocument;
use Modules\User\Models\UserLoginHistory;
use Mrj\Foundation\Tests\TestCase;

/**
 * The base user model doesn't define devices()/firebaseTokens()/documents()/
 * loginHistory()/latestLogin(): each module registers its own with
 * User::resolveRelationUsing() in its ServiceProvider::boot(), so the base
 * model imports no module classes. This proves those registrations resolve.
 */
class DynamicUserRelationsTest extends TestCase
{
    public function test_devices_relation_resolves(): void
    {
        $user = User::factory()->create();
        $device = Device::create(['user_id' => $user->id, 'device_id' => 'abc']);

        $this->assertTrue($user->devices()->exists());
        $this->assertTrue($user->devices->contains($device));
    }

    public function test_firebase_tokens_relation_resolves(): void
    {
        $user = User::factory()->create();
        $token = FirebaseToken::create(['user_id' => $user->id, 'token' => 'tok-1']);

        $this->assertTrue($user->firebaseTokens()->exists());
        $this->assertTrue($user->firebaseTokens->contains($token));
    }

    public function test_documents_relation_resolves(): void
    {
        $user = User::factory()->create();
        $document = UserDocument::create([
            'user_id' => $user->id,
            'document_type' => 'nid',
            'document_number' => '12345',
            'file_path' => 'documents/nid.pdf',
        ]);

        $this->assertTrue($user->documents()->exists());
        $this->assertTrue($user->documents->contains($document));
    }

    public function test_login_history_and_latest_login_relations_resolve(): void
    {
        $user = User::factory()->create();
        UserLoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'logged_in_at' => now()->subDay(),
        ]);
        $latest = UserLoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'logged_in_at' => now(),
        ]);

        $this->assertCount(2, $user->loginHistory);
        $this->assertTrue($user->latestLogin->is($latest));
    }
}
