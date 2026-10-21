<?php

namespace App\Domain\Media;

/**
 * Sizes an uploaded image is converted to.
 * Values are used as keys in store_settings.image_dimensions, in the `conversions` array
 * of a stored image, and in conversion file names — don't rename them.
 */
enum ImageConversion: string
{
    case Miniature = 'miniature';
    case Main = 'main';

    public function label(): string
    {
        return __("admin.design.image_settings.conversions.{$this->value}");
    }

    /**
     * How the image is fitted into width × height:
     *  - cover:   fills the whole box, the excess is cropped (thumbnails in a grid look even)
     *  - contain: the whole image fits inside, empty space is filled with white (nothing is cut off)
     */
    public function fit(): string
    {
        return match ($this) {
            self::Miniature => 'cover',
            self::Main => 'contain',
        };
    }

    /**
     * Used when a store hasn't saved its own sizes (fresh install, seeders, a newly added type).
     * Chosen so Lighthouse doesn't flag images as too small or too large.
     *
     * @return array{width: int, height: int}
     */
    public function defaultDimensions(): array
    {
        return match ($this) {
            self::Miniature => ['width' => 600, 'height' => 600],
            self::Main => ['width' => 2000, 'height' => 2000],
        };
    }
}