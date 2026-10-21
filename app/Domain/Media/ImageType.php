<?php

namespace App\Domain\Media;

use App\Models\Blog\BlogPost;
use App\Models\Blog\BlogTag;
use App\Models\Catalog\AttributeValue;
use App\Models\Catalog\Category;
use App\Models\Catalog\Manufacturer;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\ProductDescription;
use App\Models\Catalog\Tag;

/**
 * Every kind of image in the shop, in one place.
 *
 * Values are used as keys in store_settings.image_dimensions and as directory names
 * ({store}/images/{type}/...) — don't rename them. 'blog' keeps its old name for that reason.
 *
 * Adding a type: add a case here, then the model declares it in imageColumns().
 * DesignImageSettings and ProcessStagedImage pick it up automatically.
 */
enum ImageType: string
{
    case Logo           = 'logo';
    case Product        = 'product';
    case Category       = 'category';
    case Manufacturer   = 'manufacturer';
    case Tag            = 'tag';
    case BlogPost       = 'blog';
    case BlogTag        = 'blog_tag';
    case Option         = 'option';
    case Attribute      = 'attribute';

    public function label(): string
    {
        return __("admin.design.image_settings.types.{$this->value}");
    }

    /**
     * Which sizes this type is converted to
     *
     * @return array<ImageConversion>
     */
    public function conversions(): array
    {
        return match ($this) {
            self::Logo, self::Option, self::Attribute => [ImageConversion::Miniature],
            default => [ImageConversion::Miniature, ImageConversion::Main],
        };
    }

    /**
     * The model that stores images of this type (reverse direction: type → model).
     * Not used by the conversion pipeline itself; it's for jobs like
     * "regenerate all conversions of a type" after its sizes were changed.
     *
     * @return class-string|null
     */
    public function modelClass(): ?string
    {
        return match ($this) {
            self::Logo          => null, // stored in StoreSettings, not a catalog entity
            self::Product       => ProductDescription::class, // product images are store-scoped
            self::Category      => Category::class,
            self::Manufacturer  => Manufacturer::class,
            self::Tag           => Tag::class,
            self::BlogPost      => BlogPost::class,
            self::BlogTag       => BlogTag::class,
            self::Option        => OptionValue::class,
            self::Attribute     => AttributeValue::class,
        };
    }

    /**
     * Final sizes for this type: what the store saved, or the default for each conversion.
     * Only conversions declared in conversions() are returned — stale keys in settings are ignored.
     *
     * @param  array  $saved  store_settings.image_dimensions (the whole array, all types)
     * @return array<string, array{width: int, height: int}>  conversion value => size
     */
    public function dimensions(array $saved): array
    {
        $result = [];

        foreach ($this->conversions() as $conversion) {
            $default = $conversion->defaultDimensions();
            $size = $saved[$this->value][$conversion->value] ?? [];

            // Empty input in the settings form means "use the default"
            $result[$conversion->value] = [
                'width'     => (int) ($size['width'] ?? 0) ?: $default['width'],
                'height'    => (int) ($size['height'] ?? 0) ?: $default['height'],
            ];
        }

        return $result;
    }
}