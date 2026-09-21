<?php

namespace Modules\ImportDownloadManager\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Override;
use OwenIt\Auditing\Auditable;

class DownloadImportManager extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use Auditable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'title',
        'url',
        'remarks',
        'status',
        'type',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'type' => ImportType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
