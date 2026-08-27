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
    case FacetPages     = 'catalog.facet_pages';
    case Attributes     = 'catalog.attributes';
    case Options        = 'catalog.options';
    case Tags           = 'catalog.tags';
    case FacetFilter    = 'catalog.facet_filter';

    // Order
    case Orders         = 'orders.orders';
    case Returns        = 'orders.returns';
    case Statuses       = 'orders.statuses';
    case Delivery       = 'orders.delivery';
    case Payment        = 'orders.payment';

    // Warehouse
    case StockStatus    = 'stock.status';
    case StockMovements = 'stock.movements';

    // Customers
    case Customers      = 'customers.customer';
    case CustomerGroups = 'customers.customer_groups';
    case Notifications  = 'customers.notifications';

    // Blog
    case BlogPosts      = 'blog.posts';
    case BlogAuthors    = 'blog.authors';
    case BlogTags       = 'blog.tags';
    case BlogComments   = 'blog.comments';

    // SEO
    case RobotsEditor   = 'seo.robots_editor';
    case Sitemap        = 'seo.sitemap';
    case Keywords       = 'seo.keywords';
    case MetaEditor     = 'seo.meta_editor';
    case Analytics      = 'seo.analytics';
    case GoogleAds      = 'seo.google_ads';
    case Slugs          = 'seo.slugs';
    
    // Design
    case LayoutEditor   = 'layout_editor';
    case MenuEditor     = 'menu_editor';
    case ImageSettings  = 'image_settings';
    case CssEditor      = 'design.css_editor';

    // Store settings
    case InfoPages      = 'info_pages';
    case StoreHomepage  = 'store_homepage';
    case StoreContacts  = 'store_contacts';
    case StoreSettings  = 'store_settings';
    case Taxes          = 'store.taxes';

    // Global settings
    case Languages      = 'global.languages';
    case Currencies     = 'global.currencies';
    case Countries      = 'global.countries';
    case Users          = 'users';
    case Stores         = 'stores';
    case StoreWizard    = 'global.store_wizard';
    

    // Parent-child hierarchy
    // In any sort order, items are sorted in sort() method 
    public function parentGroups(): NavigationGroup
    {
        return match($this) {
            
            // Catalog
            self::Products, self::Categories, self::Manufacturers, self::FacetPages, self::Attributes, self::Options, self::Tags, self::FacetFilter,
                => NavigationGroup::Catalog,

            // Orders
            self::Orders, self::Returns, self::Statuses,  self::Delivery, self::Payment,
                => NavigationGroup::Orders,

            // Stock
            self::StockStatus, self::StockMovements,
                => NavigationGroup::Stock,

            // Customers
            self::Customers, self::CustomerGroups, self::Notifications,
                => NavigationGroup::Customers,

            // Blog
            self::BlogPosts, self::BlogAuthors, self::BlogTags, self::BlogComments,
                => NavigationGroup::Blog,

            // Seo
            self::RobotsEditor, self::Slugs, self::Sitemap, self::Keywords, self::MetaEditor, self::Analytics, self::GoogleAds,
                => NavigationGroup::Seo,

            // Design
            self::MenuEditor, self::LayoutEditor, self::CssEditor, self::ImageSettings, 
                => NavigationGroup::Design,
            
            // Store settings
            self::StoreHomepage, self::InfoPages, self::StoreContacts, self::StoreSettings, self::Taxes,
                => NavigationGroup::StoreSettings,

            // Global settings
            self::Stores, self::Countries, self::Languages, self::Currencies, self::Users, self::StoreWizard,
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
            self::FacetPages        => 'heroicon-o-squares-plus',
            self::Attributes        => 'heroicon-o-list-bullet',
            self::Options           => 'heroicon-o-adjustments-horizontal',
            self::Tags              => 'heroicon-o-tag',
            self::FacetFilter       => 'heroicon-o-adjustments-vertical',

            // Orders
            self::Orders            => 'heroicon-o-banknotes',
            self::Returns           => 'heroicon-o-arrow-path-rounded-square',
            self::Statuses          => 'heroicon-o-check-circle',
            self::Delivery          => 'heroicon-o-truck',
            self::Payment           => 'heroicon-o-credit-card',

            // Stock
            self::StockStatus       => 'heroicon-o-archive-box',
            self::StockMovements    => 'heroicon-o-arrows-right-left',

            // Customers
            self::Customers         => 'heroicon-o-user',
            self::CustomerGroups    => 'heroicon-o-users',
            self::Notifications     => 'heroicon-o-paper-airplane',

            // Blog
            self::BlogPosts         => 'heroicon-o-pencil-square',
            self::BlogAuthors       => 'heroicon-o-user-circle',
            self::BlogTags          => 'heroicon-o-hashtag',
            self::BlogComments      => 'heroicon-o-chat-bubble-left',

            // SEO
            self::Analytics         => 'heroicon-o-chart-bar',
            self::MetaEditor        => 'heroicon-o-puzzle-piece',
            self::Keywords          => 'heroicon-o-bars-3-bottom-left',
            self::Sitemap           => 'heroicon-o-share',
            self::Slugs             => 'heroicon-o-link',
            self::RobotsEditor      => 'heroicon-o-code-bracket',
            self::GoogleAds         => 'heroicon-o-rss',

            // Design
            self::MenuEditor        => 'heroicon-s-queue-list',
            self::LayoutEditor      => 'heroicon-o-paint-brush',
            self::CssEditor         => 'heroicon-o-code-bracket',
            self::ImageSettings     => 'heroicon-o-photo',

            // Store settings
            self::StoreHomepage     => 'heroicon-o-home',
            self::InfoPages         => 'heroicon-o-information-circle',
            self::StoreContacts     => 'heroicon-o-at-symbol',
            self::StoreSettings     => 'heroicon-o-cog-6-tooth',
            self::Taxes             => 'heroicon-o-divide',

            // Global settings
            self::Languages         => 'heroicon-o-language',
            self::Currencies        => 'heroicon-o-currency-dollar',
            self::Countries         => 'heroicon-o-flag',
            self::Users             => 'heroicon-o-lock-closed',
            self::Stores            => 'heroicon-o-globe-alt',
            self::StoreWizard       => 'heroicon-o-cursor-arrow-rays',

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
            self::FacetPages        => 4,
            self::Attributes        => 5,
            self::Options           => 6,
            self::Tags              => 7,
            self::FacetFilter       => 8,

            // Customers
            self::Customers         => 1,
            self::CustomerGroups    => 2,
            self::Notifications     => 3,

            // Orders
            self::Orders            => 1,
            self::Returns           => 2,
            self::Statuses          => 3,
            self::Delivery          => 4,
            self::Payment           => 5,

            // Stock
            self::StockStatus       => 1,
            self::StockMovements    => 2,

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
            self::GoogleAds         => 7,

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
            self::Taxes             => 5,

            // Global settings
            self::Languages         => 1,
            self::Currencies        => 2,
            self::Countries         => 3,
            self::Users             => 4,
            self::Stores            => 5,
            self::StoreWizard       => 6,
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