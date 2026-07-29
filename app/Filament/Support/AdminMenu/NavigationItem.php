<?php
namespace App\Filament\Support\AdminMenu;
use App\Filament\Support\AdminMenu\NavigationGroup;

enum NavigationItem: string
{
    // Child menu items
    // Catalog
    case Products       = 'catalog.products';
    case Categories     = 'catalog.categories';
    case Manufacturers  = 'catalog.manufacturers';
    case Attributes     = 'catalog.attributes';
    case Options        = 'catalog.options';
    case Tags           = 'catalog.tags';
    case FacetFilter    = 'catalog.facet_filter';

    // Order
    case Orders         = 'orders.orders';
    case Returns        = 'orders.returns';
    case Statuses       = 'orders.statuses';

    // Customers
    case Customers      = 'customers.customer';
    case CustomerGroups = 'customers.customer_groups';

    // Blog
    case BlogPosts      = 'blog.posts';
    case BlogAuthors    = 'blog.authors';
    case BlogTags       = 'blog.tags';
    case BlogComments   = 'blog.comments';

    // SEO
    case RobotsEditor   = 'robots_editor';
    case Sitemap        = 'seo.sitemap';
    case Keywords       = 'seo.keywords';
    case MetaEditor     = 'seo.meta_editor';
    case Analytics      = 'seo.analytics';
    case Slugs          = 'seo.slugs';
    
    // Design
    case LayoutEditor   = 'layout_editor';
    case MenuEditor     = 'menu_editor';
    case ImageSettings  = 'image_settings';
    case CssEditor      = 'css_editor';

    // Store settings
    case StoreHomepage  = 'store_homepage';
    case StoreContacts  = 'store_contacts';
    case StoreSettings  = 'store_settings';
    case InfoPages      = 'info_pages';

    // Global settings
    case Stores         = 'stores';
    case Countries      = 'countries';
    case Users          = 'users';
    case Languages      = 'languages';
    case Currencies     = 'currencies';
    

    // Parent-child hierarchy
    // In any sort order, items are sorted in sort() method 
    public function parentGroups(): NavigationGroup
    {
        return match($this) {
            
            // Catalog
            self::Products, self::Categories, self::Manufacturers, self::Attributes, self::Options, self::Tags, self::FacetFilter,
                => NavigationGroup::Catalog,

            // Orders
            self::Orders, self::Returns, self::Statuses,
                => NavigationGroup::Orders,

            // Customers
            self::Customers, self::CustomerGroups,
                => NavigationGroup::Customers,

            // Blog
            self::BlogPosts, self::BlogAuthors, self::BlogTags, self::BlogComments,
                => NavigationGroup::Blog,

            // Seo
            self::RobotsEditor, self::Slugs, self::Sitemap, self::Keywords, self::MetaEditor, self::Analytics, 
                => NavigationGroup::Seo,

            // Design
            self::MenuEditor, self::LayoutEditor, self::CssEditor, self::ImageSettings, 
                => NavigationGroup::Design,
            
            // Store settings
            self::StoreSettings, self::InfoPages, self::StoreContacts, self::StoreHomepage
                => NavigationGroup::StoreSettings,

            // Global settings
            self::Stores, self::Countries, self::Languages, self::Currencies, self::Users
                => NavigationGroup::GlobalSettings,
        };
    }

    // Child icons
    public function icon(): string
    {
        return match($this) {
            // Catalog
            self::Products          => 'heroicon-o-shopping-cart',
            self::Categories        => 'heroicon-o-squares-2x2',
            self::Manufacturers     => 'heroicon-o-home-modern',
            self::Attributes        => 'heroicon-o-list-bullet',
            self::Options           => 'heroicon-o-adjustments-horizontal',
            self::Tags              => 'heroicon-o-tag',
            self::FacetFilter       => 'heroicon-o-adjustments-vertical',

            // Orders
            self::Orders            => 'heroicon-o-banknotes',
            self::Returns           => 'heroicon-o-arrows-right-left',
            self::Statuses          => 'heroicon-o-check-circle',

            // Customers
            self::Customers         => 'heroicon-o-user',
            self::CustomerGroups    => 'heroicon-o-users',

            // Blog
            self::BlogPosts         => 'heroicon-o-pencil-square',
            self::BlogAuthors       => 'heroicon-o-user-circle',
            self::BlogTags          => 'heroicon-o-hashtag',
            self::BlogComments      => 'heroicon-o-chat-bubble-left',

            // SEO
            self::Analytics         => 'heroicon-o-chart-bar',
            self::MetaEditor        => 'heroicon-o-wrench-screwdrive',
            self::Keywords          => 'heroicon-o-table-cells',
            self::Sitemap           => 'heroicon-o-share',
            self::Slugs             => 'heroicon-o-link',
            self::RobotsEditor      => 'heroicon-o-code-bracket',

            // Design
            self::MenuEditor        => 'heroicon-s-squares-plus',
            self::LayoutEditor      => 'heroicon-o-paint-brush',
            self::CssEditor         => 'heroicon-o-code-bracket',
            self::ImageSettings     => 'heroicon-o-photo',

            // Store settings
            self::StoreHomepage     => 'heroicon-o-home',
            self::InfoPages         => 'heroicon-o-information-circle',
            self::StoreContacts     => 'heroicon-o-at-symbol',
            self::StoreSettings     => 'heroicon-o-cog-6-tooth',

            // Global settings
            self::Stores            => 'heroicon-o-globe-alt',
            self::Countries         => 'heroicon-o-flag',
            self::Users             => 'heroicon-o-lock-closed',
            self::Languages         => 'heroicon-o-language',
            self::Currencies        => 'heroicon-o-currency-dollar',
        };
    }

    // Child sort order
    public function sort(): int
    {
        return match($this) {

            // Catalog
            self::Products          => 1,
            self::Categories        => 2,
            self::Manufacturers     => 3,
            self::Attributes        => 4,
            self::Options           => 5,
            self::Tags              => 6,
            self::FacetFilter       => 7,

            // Customers
            self::Customers         => 1,
            self::CustomerGroups    => 2,
            self::Statuses          => 3,

            // Blog
            self::BlogPosts         => 1,
            self::BlogTags          => 2,
            self::BlogAuthors       => 3,
            self::BlogComments      => 4,

            // SEO
            self::Keywords          => 1,
            self::MetaEditor        => 2,
            self::Analytics         => 3,
            self::Sitemap           => 4,
            self::Slugs             => 5,
            self::RobotsEditor      => 6,

            // Design
            self::MenuEditor        => 1,
            self::LayoutEditor      => 2,
            self::CssEditor         => 3,
            self::ImageSettings     => 4,

            // Store settings
            self::StoreHomepage     => 1,
            self::InfoPages         => 2,
            self::StoreContacts     => 3,
            self::StoreSettings     => 4,

            // Global settings
            self::Languages         => 1,
            self::Currencies        => 2,
            self::Countries         => 3,
            self::Users             => 4,
            self::Stores            => 5,
        };
    }

    // Child item label
    public function labelPlural(): string
    {
        return __("admin.{$this->value}.navigation_label");
    }

    public function labelSingular(): string
    {
        return __("admin.{$this->value}.model_label_singular");
    }
}