<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature\OfflineSync;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mrj\Foundation\Models\Audit;
use Mrj\Foundation\Sync\Syncable;
use Mrj\Foundation\Tests\TestCase;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * A ULID-keyed model must be auditable: `audits.auditable_id` used to be an
 * integer column, so the audit insert failed and took the save down with it.
 */
class AuditedSyncableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests run in the console, where auditing is off by default.
        config(['audit.console' => true]);

        Schema::create('audited_notes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->timestamps();
            $table->syncable();
        });

        Relation::morphMap(['audited_note' => AuditedNote::class]);
    }

    /**
     * SQLite would store a ULID in an integer column anyway; PostgreSQL and
     * MySQL refuse it. Guard the column type itself.
     */
    public function test_auditable_id_is_a_string_column(): void
    {
        $this->assertContains(Schema::getColumnType('audits', 'auditable_id'), ['varchar', 'string', 'character varying']);
    }

    public function test_changes_to_a_ulid_model_are_audited(): void
    {
        $this->actingAs(User::factory()->create());

        $note = AuditedNote::create(['title' => 'First']);
        $note->update(['title' => 'Second']);

        $audits = Audit::query()->where('auditable_type', 'audited_note')->where('auditable_id', $note->id)->get();

        $this->assertSame(['created', 'updated'], $audits->pluck('event')->all());
        $this->assertSame($note->id, $audits->first()->auditable_id);
    }

    public function test_integer_keyed_models_are_still_audited_and_found(): void
    {
        $user = User::factory()->create();
        $user->update(['name' => 'Renamed']);

        $this->assertTrue(
            Audit::query()->where('auditable_type', 'user')->where('auditable_id', $user->id)->where('event', 'updated')->exists()
        );
    }
}

class AuditedNote extends Model implements AuditableContract
{
    use Auditable, Syncable;

    protected $table = 'audited_notes';

    protected $fillable = ['title'];
}
