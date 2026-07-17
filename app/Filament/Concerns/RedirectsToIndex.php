<?php

namespace App\Filament\Concerns;

/**
 * Redirect to list page from edit page on save
 * Usage:
 * Add to use block:
 * use App\Filament\Concerns\RedirectsToIndex;
 * Add inside class:
 * use RedirectsToIndex;
 */
trait RedirectsToIndex
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}