<?php

namespace App\Domain\Support\Concerns;

trait InvalidatesNavigationBadges
{
    protected static function navigationBadgeResources(): array
    {
        return [];
    }

    public static function bootInvalidatesNavigationBadges(): void
    {
        static::saved(fn (self $model) => $model->flushNavigationBadgeCaches());
        static::deleted(fn (self $model) => $model->flushNavigationBadgeCaches());
    }

    public function flushNavigationBadgeCaches(): void
    {
        $storeId = $this->resolveNavigationBadgeStoreId();

        foreach (static::navigationBadgeResources() as $resourceClass) {
            $resourceClass::forgetNavigationBadgeCache($storeId);
        }
    }

    protected function resolveNavigationBadgeStoreId(): ?int
    {
        return $this->store_id ?? null;
    }
}