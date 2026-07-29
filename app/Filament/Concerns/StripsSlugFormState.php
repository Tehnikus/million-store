<?php

namespace App\Filament\Concerns;

/**
 * Removes slugs from form state before proceeding state to save to DB pipeline
 * 
 * This is required for Model::shouldBeStrict
 */
trait StripsSlugFormState
{
    protected function stripSlugFormState(array $data): array
    {
        unset($data['slugs'], $data['slugs_touched']);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->stripSlugFormState($value);
            }
        }

        return $data;
    }
}