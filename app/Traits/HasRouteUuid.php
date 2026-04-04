<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasRouteUuid
{
    protected static function bootHasRouteUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
