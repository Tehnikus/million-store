<?php

namespace App\Filament\Support\AdminMenu;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;

trait HasCachedNavigationBadge
{
    private const string EMPTY_BADGE_SENTINEL = '__empty__';

    public static function getNavigationBadge(): ?string
    {
        $value = Cache::rememberForever(
            static::navigationBadgeCacheKey(),
            fn () => static::computeNavigationBadge() ?? self::EMPTY_BADGE_SENTINEL,
        );

        return $value === self::EMPTY_BADGE_SENTINEL ? null : $value;
    }

    protected static function navigationBadgeCacheKey(): string
    {
        return 'nav_badge:' . static::class . ':' . static::resolveScope();
    }

    public static function forgetNavigationBadgeCache(?int $storeId = null): void
    {
        $scope = static::isScopedToTenant()
            ? ($storeId ?? Filament::getTenant()?->id ?? 'global')
            : 'global';

        Cache::forget('nav_badge:' . static::class . ':' . $scope);
    }

    private static function resolveScope(): string
    {
        if (! static::isScopedToTenant()) {
            return 'global';
        }

        return (string) (Filament::getTenant()?->id ?? 'global');
    }

    abstract protected static function computeNavigationBadge(): ?string;
}