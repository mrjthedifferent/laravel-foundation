<?php

namespace Mrj\Foundation\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Mrj\Foundation\Services\FileManagerService;

trait HasImageAttribute
{
    /**
     * Boot the trait: delete the stored file(s) of every column named in
     * $imageFields when the model is hard-deleted.
     */
    protected static function bootHasImageAttribute(): void
    {
        static::deleting(function ($model): void {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            foreach ($model->imageFields ?? [] as $field) {
                FileManagerService::deleteFile($model->getRawOriginal($field));
            }
        });
    }

    /**
     * A real Eloquent attribute cast for an image/file column — define it
     * from the model exactly like any other Attribute:
     *
     *   protected function image(): Attribute
     *   {
     *       return $this->imageAttribute(column: 'image', defaultImage: 'images/person.png', directory: 'images/users');
     *   }
     *
     * Because this registers as a genuine cast, $model->toArray()/toJson()
     * and $model->image agree on the value — the previous getAttribute()/
     * setAttribute() override transformed the value only on property access,
     * so serialization silently saw the raw path instead, and assigning the
     * attribute wrote to disk immediately rather than through the cast
     * pipeline (so it wasn't rolled back if the surrounding save failed).
     *
     * Deliberately not return-type-hinted as Attribute: Eloquent discovers
     * attribute-cast methods by reflecting the class for exactly that return
     * type and invoking every match with zero arguments — hinting it here
     * would make Eloquent try to call this (parameterized) helper directly.
     *
     * @return Attribute
     */
    protected function imageAttribute(string $column, ?string $defaultImage = 'images/default.png', ?string $directory = null)
    {
        $directory ??= 'uploads/'.$this->getTable();

        return Attribute::make(
            get: fn ($value) => FileManagerService::getImage($value, default: $defaultImage),
            set: function ($value) use ($column, $directory) {
                if ($value === null) {
                    FileManagerService::deleteFile($this->getRawOriginal($column));

                    return null;
                }

                return FileManagerService::uploadFile(
                    $value,
                    existing_file: $this->getRawOriginal($column),
                    directory: $directory,
                );
            },
        )
            // The value assigned is often an UploadedFile object; Eloquent's
            // default object-caching would otherwise hand that same raw
            // object back on the next read within the same request, instead
            // of the stored path this cast's get() resolves to a URL.
            ->withoutObjectCaching();
    }
}
