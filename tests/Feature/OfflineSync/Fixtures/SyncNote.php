<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature\OfflineSync\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Mrj\Foundation\Sync\Syncable;

/**
 * @property string $id
 * @property int $owner_id
 * @property string $title
 * @property string|null $body
 * @property int $version
 */
class SyncNote extends Model
{
    use Syncable;

    protected $table = 'sync_notes';

    protected $fillable = ['title', 'body'];
}
