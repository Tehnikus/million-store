<?php

namespace App\Domain\Catalog\Concerns;

use App\Domain\Catalog\Actions\DeletionObserver;

/**
 * Every catalog model deletion goes through one transaction and one batch of product updates,
 * see DeletionObserver::batch(). Nested deletes (values of a deleted group) join the outer batch.
 */
trait BatchesCatalogDeletion
{
    public function delete()
    {
        return DeletionObserver::batch(fn() => parent::delete());
    }
}