<?php

namespace Modules\User\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\User\Database\Factories\UserDocumentFactory;
use Modules\User\Enum\DocumentType;
use Mrj\Foundation\Traits\HasImageAttribute;
use Override;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * User Document Model
 *
 * Represents documents uploaded by users (e.g., NID, passport, driving license).
 *
 * @property int $id
 * @property int $user_id
 * @property string $document_type Type of document (e.g., 'nid', 'passport', 'driving_license')
 * @property string|null $document_number Document identification number
 * @property string $file_path Path to the main document file
 * @property string|null $back_file_path Path to the back side of document (if applicable)
 * @property Carbon|null $expiry_date Document expiration date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 *
 * @method static Builder|UserDocument byType(string $type)
 * @method static Builder|UserDocument notExpired()
 * @method static Builder|UserDocument expired()
 */
class UserDocument extends Model implements Auditable
{
    use HasFactory, HasImageAttribute;
    use \OwenIt\Auditing\Auditable;

    /**
     * @return UserDocumentFactory
     */
    protected static function newFactory()
    {
        return UserDocumentFactory::new();
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'document_type',
        'document_number',
        'file_path',
        'back_file_path',
        'expiry_date',
    ];

    /**
     * File fields cleaned up (their stored file deleted) by HasImageAttribute
     * when the model is hard-deleted.
     */
    protected array $imageFields = ['file_path', 'back_file_path'];

    protected function filePath(): Attribute
    {
        return $this->imageAttribute(column: 'file_path', defaultImage: null, directory: 'documents/users');
    }

    protected function backFilePath(): Attribute
    {
        return $this->imageAttribute(column: 'back_file_path', defaultImage: null, directory: 'documents/users');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'document_type' => DocumentType::class,
        ];
    }

    /**
     * Relationship: User who owns this document
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter documents by type
     *
     * @param  Builder  $query
     * @param  string  $type  Document type (e.g., 'nid', 'passport')
     * @return Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope: Get only non-expired documents
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q): void {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>=', now());
        });
    }

    /**
     * Scope: Get only expired documents
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }
}
