<?php
namespace App\Filament\Support\AdminMenu;

use App\Filament\Support\AdminMenu\NavigationItem;

trait HasCentralizedNavigation
{
    abstract protected static function getMenuConfig(): NavigationItem;

    public static function getNavigationIcon(): string
    {
        return static::getMenuConfig()->icon();
    }

    public static function getNavigationSort(): ?int
    {
        return static::getMenuConfig()->sort();
    }

    public static function getNavigationLabel(): string
    {
        return static::getMenuConfig()->labelPlural();
    }

    public static function getModelLabel(): string
    {
        return static::getMenuConfig()->labelSingular();
    }

    public static function getPluralModelLabel(): string
    {
        return static::getMenuConfig()->labelPlural();
    }

    public static function getNavigationGroup(): NavigationGroup
    {
        return static::getMenuConfig()->parentGroups();
    }

    public function getHeading(): string
    {
        return static::getMenuConfig()->labelPlural();
    }

    public function getTitle(): string
    {
        return static::getMenuConfig()->labelPlural();
    }
}