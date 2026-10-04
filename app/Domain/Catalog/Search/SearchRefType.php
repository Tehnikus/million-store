<?php

namespace App\Domain\Catalog\Search;

/**
 * Kinds of entities that can take part in a product's search index.
 *
 * The string values are the JSON keys used in
 *  - product_search_index.search_included_refs  (derived, rebuilt by SearchIndexer)
 *  - product_descriptions.search_excluded_refs  (manual, edited in the product form)
 * so do not rename them casually: existing rows would stop matching.
 *
 * Not the same as FacetType: facet_index stores attribute/option VALUES only (the parent is facet_group_id),
 * while the search index also needs the attribute/option GROUP itself, because renaming "Screen"
 * must rebuild different products than renaming "OLED".
 */
enum SearchRefType: string
{
    case Category       = 'category';
    case Manufacturer   = 'manufacturer';
    case Attribute      = 'attribute';
    case AttributeValue = 'attribute_value';
    case Option         = 'option';
    case OptionValue    = 'option_value';
    case Tag            = 'tag';
}