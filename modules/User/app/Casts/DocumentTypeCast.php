<?php

namespace Modules\User\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use Modules\User\Enum\DocumentType;

/**
 * Like an enum cast to DocumentType, but a stored type the enum doesn't list
 * (a legacy value, or one a project added) comes back as its raw string
 * instead of throwing, so a page that lists documents still renders.
 *
 * @implements CastsAttributes<DocumentType|string|null, DocumentType|string|null>
 */
class DocumentTypeCast implements CastsAttributes, SerializesCastableAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): DocumentType|string|null
    {
        if ($value === null) {
            return null;
        }

        return DocumentType::tryFrom((string) $value) ?? (string) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value instanceof DocumentType ? $value->value : $value;
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value instanceof DocumentType ? $value->value : $value;
    }
}
