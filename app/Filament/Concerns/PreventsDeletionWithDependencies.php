<?php

namespace App\Filament\Concerns;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

trait PreventsDeletionWithDependencies
{
    /**
     * relations that block deleteions: restrictOnDelete on foreign key
     * Expected format: ['relationName' => ResourceClass::class] or ['relationName' => 'Human readable title']
     */
    protected static function restrictedDependencies(): array
    {
        return [];
    }

    /**
     * Relations that DO NOT block deletion but invoke cascade dele of other entries: cascadeOnDelete on foreign key
     * Expected format: ['relationName' => ResourceClass::class] or ['relationName' => 'Human readable title']
     */
    protected static function cascadingDependencies(): array
    {
        return [];
    }

    /**
     * Call delete check in DeleteAction:
     * Actions\DeleteAction::make() -> static::applyDeletionGuards(...)
     */
    public static function applyDeletionGuards(DeleteAction $action): DeleteAction
    {
        return $action
            ->before(function (Model $record, DeleteAction $action) {
                $blocking = static::collectDependencyCounts($record, static::restrictedDependencies());

                if ($blocking === []) {
                    return;
                }

                Notification::make()
                    ->danger()
                    ->title(__('admin.messages.delete_restricted_title'))
                    ->body(static::formatDependencyList($blocking))
                    ->persistent()
                    ->send();

                $action->cancel();
            })
            ->modalDescription(function (Model $record) {
                $cascading = static::collectDependencyCounts($record, static::cascadingDependencies());

                if ($cascading === []) {
                    return null; // Standart filament modal window if no dependencies found
                }

                return __('admin.messages.delete_cascade_warning')
                    . "\n\n" . static::formatDependencyList($cascading);
            });
    }

    protected static function collectDependencyCounts(Model $record, array $map): array
    {
        $found = [];

        foreach ($map as $relation => $resourceOrLabel) {
            if (! method_exists($record, $relation)) {
                continue;
            }

            $count = $record->{$relation}()->count();

            if ($count > 0) {
                $found[] = [
                    'label' => static::resolveLabel($resourceOrLabel),
                    'count' => $count,
                ];
            }
        }

        return $found;
    }

    protected static function resolveLabel(string $resourceOrLabel): string
    {
        return is_subclass_of($resourceOrLabel, Resource::class)
            ? $resourceOrLabel::getNavigationLabel()
            : $resourceOrLabel;
    }

    protected static function formatDependencyList(array $dependencies): string
    {
        return collect($dependencies)
            ->map(fn ($dep) => "- {$dep['label']}: {$dep['count']}")
            ->implode("\n");
    }
}