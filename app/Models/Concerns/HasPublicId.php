<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::created(function ($model): void {
            if (! empty($model->public_id)) {
                return;
            }

            $model->public_id = self::formatPublicId($model->getKey());
            DB::table($model->getTable())
                ->where($model->getKeyName(), $model->getKey())
                ->update(['public_id' => $model->public_id]);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function getPublicIdDisplayAttribute(): string
    {
        $publicId = (string) ($this->public_id ?? '');

        return ltrim($publicId, '0') ?: '0';
    }

    protected static function formatPublicId(int|string $id): string
    {
        return (string) $id;
    }
}