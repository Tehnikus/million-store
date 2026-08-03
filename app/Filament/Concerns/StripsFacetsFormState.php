<?php

namespace App\Filament\Concerns;

/**
 * Removes facets from form state before proceeding state to save to DB pipeline
 * This is required for Model::shouldBeStrict
 */
trait StripsFacetsFormState
{
    protected function stripFacetsFormState(array $data): array
    {
        unset($data['facet_categories'], $data['facet_manufacturers'], $data['facet_attributes'], $data['facet_tags'], $data['facet_options']);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->stripFacetsFormState($value);
            }
        }

        return $data;
    }
}