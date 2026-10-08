<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Mrj\Foundation\Http\Middleware\EnsureIdempotency;
use Mrj\Foundation\Models\IdempotencyKey;
use Mrj\Foundation\Tests\TestCase;
use RuntimeException;

class IdempotencyTest extends TestCase
{
    private static int $calls = 0;

    protected function setUp(): void
    {
        parent::setUp();

        self::$calls = 0;

        Route::middleware(['api', 'idempotent'])->post('/api/_idem/create', function () {
            self::$calls++;

            return response()->json(['created' => self::$calls], 201);
        });

        Route::middleware(['api', 'idempotent:required'])->post('/api/_idem/strict', fn () => response()->json(['ok' => true]));

        Route::middleware(['api', 'idempotent'])->post('/api/_idem/boom', function () {
            self::$calls++;

            return response()->json(['error' => 'down'], 503);
        });

        Route::middleware(['api', 'idempotent'])->post('/api/_idem/throws', function (): never {
            self::$calls++;

            throw new RuntimeException('failed');
        });
    }

    private function key(): string
    {
        return '01J9ZK3Q7X4V2M8N6P5R3T1W0Y';
    }

    public function test_requests_without_a_key_run_normally(): void
    {
        $this->postJson('/api/_idem/create', ['a' => 1])->assertCreated();
        $this->postJson('/api/_idem/create', ['a' => 1])->assertCreated();

        $this->assertSame(2, self::$calls);
        $this->assertDatabaseCount('idempotency_keys', 0);
    }

    public function test_a_retry_with_the_same_key_replays_the_first_response(): void
    {
        $headers = [EnsureIdempotency::HEADER => $this->key()];

        $first = $this->postJson('/api/_idem/create', ['a' => 1], $headers)->assertCreated();
        $second = $this->postJson('/api/_idem/create', ['a' => 1], $headers)->assertCreated();

        $this->assertSame(1, self::$calls);
        $this->assertSame($first->json(), $second->json());
        $second->assertHeader(EnsureIdempotency::REPLAYED_HEADER, 'true');
        $first->assertHeaderMissing(EnsureIdempotency::REPLAYED_HEADER);
    }

    public function test_the_same_key_with_a_different_body_is_refused(): void
    {
        $headers = [EnsureIdempotency::HEADER => $this->key()];

        $this->postJson('/api/_idem/create', ['a' => 1], $headers)->assertCreated();
        $this->postJson('/api/_idem/create', ['a' => 2], $headers)->assertUnprocessable();

        $this->assertSame(1, self::$calls);
    }

    public function test_a_key_still_in_progress_answers_409(): void
    {
        IdempotencyKey::create([
            'scope' => 'ip:127.0.0.1',
            'key' => $this->key(),
            'method' => 'POST',
            'path' => '/api/_idem/create',
            'request_hash' => hash('sha256', 'POST|api/_idem/create|'.json_encode(['a' => 1])),
            'expires_at' => now()->addHour(),
        ]);

        $this->postJson('/api/_idem/create', ['a' => 1], [EnsureIdempotency::HEADER => $this->key()])
            ->assertStatus(409);

        $this->assertSame(0, self::$calls);
    }

    public function test_server_errors_are_not_stored_so_they_can_be_retried(): void
    {
        $headers = [EnsureIdempotency::HEADER => $this->key()];

        $this->postJson('/api/_idem/boom', [], $headers)->assertStatus(503);
        $this->postJson('/api/_idem/boom', [], $headers)->assertStatus(503);

        $this->assertSame(2, self::$calls);
        $this->assertDatabaseCount('idempotency_keys', 0);
    }

    public function test_exceptions_release_the_key(): void
    {
        $this->withoutExceptionHandling();
        $headers = [EnsureIdempotency::HEADER => $this->key()];

        try {
            $this->postJson('/api/_idem/throws', [], $headers);
            $this->fail('Expected the exception to propagate.');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertDatabaseCount('idempotency_keys', 0);
    }

    public function test_keys_are_scoped_per_user(): void
    {
        $headers = [EnsureIdempotency::HEADER => $this->key()];
        [$alice, $bob] = User::factory()->count(2)->create(['is_active' => true]);

        $this->actingAs($alice)->postJson('/api/_idem/create', ['a' => 1], $headers)->assertCreated();
        $this->actingAs($bob)->postJson('/api/_idem/create', ['a' => 1], $headers)->assertCreated();

        $this->assertSame(2, self::$calls);
    }

    public function test_an_expired_key_runs_the_request_again(): void
    {
        $headers = [EnsureIdempotency::HEADER => $this->key()];

        $this->postJson('/api/_idem/create', ['a' => 1], $headers)->assertCreated();
        $this->travel(25)->hours();
        $this->postJson('/api/_idem/create', ['a' => 1], $headers)
            ->assertCreated()
            ->assertHeaderMissing(EnsureIdempotency::REPLAYED_HEADER);

        $this->assertSame(2, self::$calls);
    }

    public function test_required_mode_rejects_a_missing_key(): void
    {
        $this->postJson('/api/_idem/strict')->assertStatus(400);
        $this->postJson('/api/_idem/strict', [], [EnsureIdempotency::HEADER => $this->key()])->assertOk();
    }

    public function test_a_malformed_key_is_rejected(): void
    {
        $this->postJson('/api/_idem/create', [], [EnsureIdempotency::HEADER => 'short'])->assertStatus(400);
        $this->assertSame(0, self::$calls);
    }

    public function test_expired_keys_are_prunable(): void
    {
        IdempotencyKey::create([
            'scope' => 'ip:1', 'key' => 'old-key-123', 'method' => 'POST', 'path' => '/x',
            'request_hash' => str_repeat('a', 64), 'expires_at' => now()->subMinute(),
        ]);
        IdempotencyKey::create([
            'scope' => 'ip:1', 'key' => 'live-key-123', 'method' => 'POST', 'path' => '/x',
            'request_hash' => str_repeat('a', 64), 'expires_at' => now()->addHour(),
        ]);

        $this->artisan('model:prune', ['--model' => [IdempotencyKey::class]])->assertSuccessful();

        $this->assertDatabaseMissing('idempotency_keys', ['key' => 'old-key-123']);
        $this->assertDatabaseHas('idempotency_keys', ['key' => 'live-key-123']);
    }
}
