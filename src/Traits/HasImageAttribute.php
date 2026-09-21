<?php

namespace Mrj\Foundation\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Mrj\Foundation\Services\FileManagerService;

trait HasImageAttribute
{
    /**
     * Boot the trait and register model event listeners
     */
    protected static function bootHasImageAttribute(): void
    {
        static::deleting(function ($model): void {
            // Skip image deletion if model is being soft deleted
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            // Check if the model has defined image fields
            if (property_exists($model, 'imageFields') && is_array($model->imageFields)) {
                foreach ($model->imageFields as $imageField) {
                    $model->deleteImage($imageField);
                }
            }
        });
    }

    /**
     * Override getAttribute to automatically handle image fields
     */
    public function getAttribute($key)
    {
        // Check if this is an image field that should be auto-handled
        if ($this->isImageField($key)
            && ! $this->hasGetMutator($key)
            && ! $this->hasAttributeMutator($key)
            && ! $this->hasCast($key)) {
            $value = parent::getAttribute($key);

            return FileManagerService::getImage($value, default: $this->getImageDefault($key));
        }

        return parent::getAttribute($key);
    }

    /**
     * Override setAttribute to automatically handle image fields
     */
    public function setAttribute($key, $value)
    {
        // Check if this is an image field that should be auto-handled
        if ($this->isImageField($key)
            && ! $this->hasSetMutator($key)
            && ! $this->hasAttributeMutator($key)
            && ! $this->hasCast($key)) {

            // Handle null values - clear the image
            if ($value === null) {
                $existingFile = $this->getRawOriginal($key);
                if ($existingFile) {
                    FileManagerService::deleteFile($existingFile);
                }

                return parent::setAttribute($key, null);
            }

            $uploadDirectory = $this->getImageDirectory($key);
            $uploadedFile = FileManagerService::uploadFile(
                $value,
                existing_file: $this->getRawOriginal($key),
                directory: $uploadDirectory
            );

            return parent::setAttribute($key, $uploadedFile);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Check if the given attribute is an image field
     */
    protected function isImageField(string $key): bool
    {
        return property_exists($this, 'imageFields')
            && is_array($this->imageFields)
            && in_array($key, $this->imageFields);
    }

    /**
     * Get the default image for a specific field
     */
    protected function getImageDefault(string $key): ?string
    {
        // Use array_key_exists instead of isset() so that an explicit null value is respected
        if (property_exists($this, 'imageDefaults') && array_key_exists($key, $this->imageDefaults)) {
            return $this->imageDefaults[$key];
        }

        return 'images/default.png';
    }

    /**
     * Get the upload directory for a specific field
     */
    protected function getImageDirectory(string $key): string
    {
        // Check if model has custom directories defined
        if (property_exists($this, 'imageDirectories') && isset($this->imageDirectories[$key])) {
            return $this->imageDirectories[$key];
        }

        return 'uploads/'.$this->getTable();
    }

    /**
     * Create an image attribute with automatic upload and retrieval
     * (This method is now optional - kept for backward compatibility)
     *
     * @param  string  $column  The database column name for the image
     * @param  string|null  $defaultImage  Default image path (default: 'images/person.png')
     * @param  string|null  $directory  Upload directory (default: 'images/{table}')
     */
    protected function imageAttribute(
        ?string $column = null,
        ?string $defaultImage = 'images/default.png',
        ?string $directory = null
    ): Attribute {
        $column ??= 'image';
        $uploadDirectory = $directory ?? 'uploads/'.$this->getTable();

        return Attribute::make(
            get: fn ($value) => FileManagerService::getImage($value, default: $defaultImage),

            set: fn ($value) => FileManagerService::uploadFile(
                $value,
                existing_file: $this->getRawOriginal($column),
                directory: $uploadDirectory
            )
        );
    }

    /**
     * Delete an existing image
     */
    protected function deleteImage(string $column): void
    {
        FileManagerService::deleteFile($this->getRawOriginal($column));
    }
}
