<?php

namespace Modules\User\Tests\Unit\Actions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Modules\Notification\Notifications\AppNotification;
use Modules\User\Actions\TrackLoginAction;
use Modules\User\Models\UserLoginHistory;
use Tests\TestCase;

class TrackLoginActionTest extends TestCase
{
    use RefreshDatabase;

    private const string CHROME_ON_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    private const string FIREFOX_ON_LINUX = 'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0';

    private function request(string $userAgent, string $ip = '203.0.113.7'): Request
    {
        return Request::create('/login', 'POST', server: ['HTTP_USER_AGENT' => $userAgent, 'REMOTE_ADDR' => $ip]);
    }

    public function test_it_records_the_login_with_parsed_device_details(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $history = app(TrackLoginAction::class)->execute($user, $this->request(self::CHROME_ON_WINDOWS));

        $this->assertDatabaseHas('user_login_history', [
            'id' => $history->id,
            'user_id' => $user->id,
            'ip_address' => '203.0.113.7',
            'browser' => 'Chrome',
            'device_type' => 'desktop',
            'logged_out_at' => null,
        ]);
        $this->assertNotNull($history->getAttribute('logged_in_at'));
    }

    public function test_the_very_first_login_does_not_raise_a_new_device_alert(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        app(TrackLoginAction::class)->execute($user, $this->request(self::CHROME_ON_WINDOWS));

        Notification::assertNothingSentTo($user);
    }

    public function test_a_login_from_an_unseen_browser_and_platform_alerts_the_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        UserLoginHistory::factory()->for($user)->create(['browser' => 'Chrome', 'platform' => 'Windows']);

        app(TrackLoginAction::class)->execute($user, $this->request(self::FIREFOX_ON_LINUX));

        Notification::assertSentTo(
            $user,
            AppNotification::class,
            fn (AppNotification $notification): bool => ($notification->data['type'] ?? null) === 'new_device_login',
        );
    }

    public function test_a_login_from_a_known_device_does_not_alert(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $action = app(TrackLoginAction::class);

        $action->execute($user, $this->request(self::CHROME_ON_WINDOWS));
        $action->execute($user, $this->request(self::CHROME_ON_WINDOWS, '198.51.100.20'));

        Notification::assertNothingSentTo($user);
        $this->assertSame(2, UserLoginHistory::where('user_id', $user->id)->count());
    }
}
