<?php

namespace App\Domain\Support\Concerns;

trait InheritsColumnFromParent
{
    protected static function bootInheritsColumnFromParent(): void
    {
        static::creating(function (self $model) {
            foreach (static::inheritedColumns() as $column => [$parentRelation, $parentColumn]) {
                if (filled($model->{$column})) {
                    continue;
                }

                $foreignKey = $model->{$parentRelation}()->getForeignKeyName();

                if (blank($model->{$foreignKey})) {
                    continue;
                }

                $model->{$column} = $model->{$parentRelation}()
                    ->getRelated()
                    ->newQuery()
                    ->whereKey($model->{$foreignKey})
                    ->value($parentColumn);
            }
        });
    }

    /** @return array<string, array{0: string, 1: string}> ['column' => [relationName, parentColumn]] */
    abstract protected static function inheritedColumns(): array;
}