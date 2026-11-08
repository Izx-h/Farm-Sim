<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Gives models string (UUID) primary keys, matching the original system's
 * non-incrementing `id` columns (e.g. "farm-…", "plan-…", "crop-maize").
 *
 * Note: we set `$incrementing` / `$keyType` via the instance initializer
 * rather than redeclaring the properties, because PHP treats a trait property
 * whose default value differs from the Eloquent base class as an incompatible
 * composition.
 */
trait UsesStringId
{
    public function initializeUsesStringId(): void
    {
        $this->setIncrementing(false);
        $this->setKeyType('string');
    }

    protected static function bootUsesStringId(): void
    {
        static::creating(function ($model) {
            if (empty($model->getKey())) {
                $model->setAttribute($model->getKeyName(), (string) Str::uuid());
            }
        });
    }
}
