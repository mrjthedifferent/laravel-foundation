<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature\OfflineSync;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Mrj\Foundation\Http\Middleware\EnsureIdempotency;
use Mrj\Foundation\Sync\SyncCursor;
use Mrj\Foundation\Tests\Feature\OfflineSync\Fixtures\SyncNote;
use Mrj\Foundation\Tests\Feature\OfflineSync\Fixtures\SyncNoteHandler;
use Mrj\Foundation\Tests\Feature\OfflineSync\Fixtures\SyncNotePolicy;
use Mrj\Foundation\Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    private User $alice;

    private User $bob;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('foundation.offline_sync.handlers', ['notes' => SyncNoteHandler::class]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sync_notes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('owner_id');
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();
            $table->syncable();
        });

        Gate::policy(SyncNote::class, SyncNotePolicy::class);

        $this->alice = User::factory()->create(['is_active' => true]);
        $this->bob = User::factory()->create(['is_active' => true]);
    }

    private function note(User $owner, string $title = 'Note'): SyncNote
    {
        $note = new SyncNote(['title' => $title]);
        $note->owner_id = $owner->id;
        $note->save();

        return $note;
    }

    /**
     * @param  list<array<string, mixed>>  $ops
     */
    private function push(User $user, array $ops): TestResponse
    {
        return $this->actingAs($user, 'sanctum')->postJson(
            '/api/v1/sync/push',
            ['ops' => $ops],
            [EnsureIdempotency::HEADER => (string) Str::ulid()],
        );
    }

    private function pull(User $user, ?string $cursor = null, int $limit = 500): TestResponse
    {
        $this->travel(3)->seconds(); // past the settle window

        return $this->actingAs($user, 'sanctum')->getJson('/api/v1/sync/pull?'.http_build_query(array_filter([
            'cursor' => $cursor,
            'limit' => $limit,
        ])));
    }

    // ── Syncable ─────────────────────────────────────────────────────────────

    public function test_syncable_models_get_a_ulid_and_a_version_that_counts_changes(): void
    {
        $note = $this->note($this->alice);

        $this->assertTrue(Str::isUlid($note->id));
        $this->assertSame(1, $note->fresh()->version);

        $note->update(['title' => 'Edited']);
        $this->assertSame(2, $note->fresh()->version);

        $note->delete();
        $this->assertSame(3, SyncNote::withTrashed()->find($note->id)->version);
    }

    // ── pull ─────────────────────────────────────────────────────────────────

    public function test_pull_returns_only_the_users_own_rows(): void
    {
        $mine = $this->note($this->alice, 'Mine');
        $this->note($this->bob, 'Theirs');

        $this->pull($this->alice)
            ->assertOk()
            ->assertJsonPath('data.changes.notes.0.id', $mine->id)
            ->assertJsonCount(1, 'data.changes.notes')
            ->assertJsonPath('data.has_more', false);
    }

    public function test_pull_pages_with_the_cursor_and_returns_each_row_once(): void
    {
        foreach (range(1, 5) as $i) {
            $this->note($this->alice, "Note {$i}");
        }

        $seen = [];
        $cursor = null;

        do {
            $data = $this->pull($this->alice, $cursor, 2)->assertOk()->json('data');
            foreach ($data['changes']['notes'] ?? [] as $row) {
                $seen[] = $row['id'];
            }
            $cursor = $data['cursor'];
        } while ($data['has_more']);

        $this->assertCount(5, $seen);
        $this->assertCount(5, array_unique($seen));

        // Nothing new: an empty page.
        $this->pull($this->alice, $cursor)->assertJsonPath('data.changes', []);
    }

    public function test_rows_changed_within_the_settle_window_wait_for_the_next_pull(): void
    {
        $this->note($this->alice);

        $this->actingAs($this->alice, 'sanctum')->getJson('/api/v1/sync/pull')
            ->assertOk()
            ->assertJsonPath('data.changes', []);
    }

    public function test_an_edit_after_a_pull_comes_back_on_the_next_pull(): void
    {
        $note = $this->note($this->alice);
        $cursor = $this->pull($this->alice)->json('data.cursor');

        $this->travel(1)->seconds();
        $note->update(['title' => 'Edited']);

        $this->pull($this->alice, $cursor)
            ->assertJsonPath('data.changes.notes.0.title', 'Edited')
            ->assertJsonPath('data.changes.notes.0.version', 2);
    }

    public function test_deleted_rows_arrive_as_tombstones(): void
    {
        $note = $this->note($this->alice);
        $cursor = $this->pull($this->alice)->json('data.cursor');

        $this->travel(1)->seconds();
        $note->delete();

        $this->pull($this->alice, $cursor)
            ->assertJsonPath('data.tombstones.notes.0', $note->id)
            ->assertJsonMissingPath('data.changes.notes');
    }

    public function test_a_garbled_cursor_starts_from_the_beginning(): void
    {
        $this->note($this->alice);

        $this->pull($this->alice, 'not-a-cursor')->assertOk()->assertJsonCount(1, 'data.changes.notes');
        $this->assertNull(SyncCursor::decode('%%%')->positionOf('notes'));
    }

    // ── push ─────────────────────────────────────────────────────────────────

    public function test_push_creates_a_row_with_the_client_id(): void
    {
        $id = (string) Str::ulid();

        $this->push($this->alice, [['name' => 'notes', 'op' => 'upsert', 'id' => $id, 'data' => ['title' => 'Offline note']]])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'applied')
            ->assertJsonPath('data.results.0.version', 1);

        $note = SyncNote::find($id);
        $this->assertSame('Offline note', $note->title);
        $this->assertSame($this->alice->id, $note->owner_id);
    }

    public function test_client_cannot_set_attributes_outside_fillable(): void
    {
        $id = (string) Str::ulid();

        $this->push($this->alice, [['name' => 'notes', 'op' => 'upsert', 'id' => $id, 'data' => ['title' => 'x', 'owner_id' => $this->bob->id, 'version' => 99]]])
            ->assertJsonPath('data.results.0.status', 'applied');

        $note = SyncNote::find($id);
        $this->assertSame($this->alice->id, $note->owner_id);
        $this->assertSame(1, $note->version);
    }

    public function test_push_updates_when_the_client_saw_the_current_version(): void
    {
        $note = $this->note($this->alice);

        $this->push($this->alice, [['name' => 'notes', 'op' => 'upsert', 'id' => $note->id, 'version' => 1, 'data' => ['title' => 'Changed']]])
            ->assertJsonPath('data.results.0.status', 'applied')
            ->assertJsonPath('data.results.0.version', 2);

        $this->assertSame('Changed', $note->fresh()->title);
    }

    public function test_a_stale_version_is_a_conflict_and_returns_the_server_row(): void
    {
        $note = $this->note($this->alice);
        $note->update(['title' => 'Server edit']); // version 2

        $this->push($this->alice, [['name' => 'notes', 'op' => 'upsert', 'id' => $note->id, 'version' => 1, 'data' => ['title' => 'Client edit']]])
            ->assertJsonPath('data.results.0.status', 'conflict')
            ->assertJsonPath('data.results.0.server.title', 'Server edit')
            ->assertJsonPath('data.results.0.version', 2);

        $this->assertSame('Server edit', $note->fresh()->title);
    }

    public function test_editing_a_row_deleted_on_the_server_is_a_conflict_with_no_server_row(): void
    {
        $note = $this->note($this->alice);
        $note->delete();

        $this->push($this->alice, [['name' => 'notes', 'op' => 'upsert', 'id' => $note->id, 'data' => ['title' => 'x']]])
            ->assertJsonPath('data.results.0.status', 'conflict')
            ->assertJsonPath('data.results.0.server', null);
    }

    public function test_push_deletes_and_repeated_deletes_are_harmless(): void
    {
        $note = $this->note($this->alice);
        $op = ['name' => 'notes', 'op' => 'delete', 'id' => $note->id, 'version' => 1];

        $this->push($this->alice, [$op])->assertJsonPath('data.results.0.status', 'applied');
        $this->assertSoftDeleted('sync_notes', ['id' => $note->id]);

        $this->push($this->alice, [$op])->assertJsonPath('data.results.0.status', 'applied');
    }

    public function test_policy_refusals_are_rejected(): void
    {
        $note = $this->note($this->alice, 'locked');

        $this->push($this->alice, [['name' => 'notes', 'op' => 'delete', 'id' => $note->id]])
            ->assertJsonPath('data.results.0.status', 'rejected');

        $this->assertNotSoftDeleted('sync_notes', ['id' => $note->id]);
    }

    public function test_another_users_row_cannot_be_changed_or_probed(): void
    {
        $theirs = $this->note($this->bob, 'Theirs');

        $this->push($this->alice, [
            ['name' => 'notes', 'op' => 'upsert', 'id' => $theirs->id, 'data' => ['title' => 'Hijack']],
            ['name' => 'notes', 'op' => 'delete', 'id' => $theirs->id],
        ])
            ->assertJsonPath('data.results.0.status', 'rejected')
            ->assertJsonPath('data.results.1.status', 'rejected');

        $this->assertSame('Theirs', $theirs->fresh()->title);
        $this->assertNotSoftDeleted('sync_notes', ['id' => $theirs->id]);
    }

    public function test_invalid_data_is_rejected_without_blocking_other_ops(): void
    {
        $good = (string) Str::ulid();

        $this->push($this->alice, [
            ['name' => 'notes', 'op' => 'upsert', 'id' => (string) Str::ulid(), 'data' => ['title' => '']],
            ['name' => 'notes', 'op' => 'upsert', 'id' => $good, 'data' => ['title' => 'Fine']],
        ])
            ->assertJsonPath('data.results.0.status', 'rejected')
            ->assertJsonStructure(['data' => ['results' => [['errors' => ['title']]]]])
            ->assertJsonPath('data.results.1.status', 'applied');

        $this->assertNotNull(SyncNote::find($good));
    }

    public function test_push_requires_an_idempotency_key_and_replays_retries(): void
    {
        $ops = ['ops' => [['name' => 'notes', 'op' => 'upsert', 'id' => (string) Str::ulid(), 'data' => ['title' => 'Once']]]];

        $this->actingAs($this->alice, 'sanctum')->postJson('/api/v1/sync/push', $ops)->assertStatus(400);

        $headers = [EnsureIdempotency::HEADER => (string) Str::ulid()];
        $this->actingAs($this->alice, 'sanctum')->postJson('/api/v1/sync/push', $ops, $headers)->assertOk();
        $this->actingAs($this->alice, 'sanctum')->postJson('/api/v1/sync/push', $ops, $headers)
            ->assertOk()
            ->assertHeader(EnsureIdempotency::REPLAYED_HEADER, 'true');

        $this->assertSame(1, SyncNote::query()->count());
    }

    public function test_unknown_collections_fail_validation(): void
    {
        $this->push($this->alice, [['name' => 'secrets', 'op' => 'upsert', 'id' => (string) Str::ulid(), 'data' => []]])
            ->assertUnprocessable();
    }

    public function test_sync_requires_authentication(): void
    {
        $this->getJson('/api/v1/sync/pull')->assertUnauthorized();
    }
}
