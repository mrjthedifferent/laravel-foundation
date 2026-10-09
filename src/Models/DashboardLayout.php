<?php

declare(strict_types=1);

namespace Mrj\Foundation\Models;

use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * One viewer's arrangement of the dashboard: which widgets, in what order, how wide,
 * and which are hidden. Widgets the viewer has never seen are not in it; the layout
 * service adds them from the defaults.
 *
 * @property int $id
 * @property int $user_id
 * @property list<array{key: string, width: int, hidden: bool}> $layout
 */
class DashboardLayout extends Model
{
    protected $fillable = ['user_id', 'layout'];

    #[Override]
    protected function casts(): array
    {
        return ['layout' => 'array'];
    }
}
