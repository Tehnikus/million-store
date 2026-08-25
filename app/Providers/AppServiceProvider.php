<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Notifications\Events\NotificationFailed;
// use Illuminate\Support\Facades\Event;
// use Illuminate\Support\Facades\URL;
// use Illuminate\Support\Facades\Vite;
// use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Route::pattern('tenant', '[0-9]+'); // Fix SQL error when tenant id is replaced with any string in address bar
        DB::prohibitDestructiveCommands(App::environment(['production'])); // Prevent destructive artisan commands on production
        Model::shouldBeStrict(!App::environment(['production']));
        // Date::use(CarbonImmutable::class);
        // URL::forceHttps(App::environment(['production']));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'product'          => \App\Models\Catalog\Product::class,
            'category'         => \App\Models\Catalog\Category::class,
            'manufacturer'     => \App\Models\Catalog\Manufacturer::class,
            'tag'              => \App\Models\Catalog\Tag::class,
            'facet_page'       => \App\Models\Catalog\FacetPage::class,
            'blog_post'        => \App\Models\Blog\BlogPost::class,
            'blog_tag'         => \App\Models\Blog\BlogTag::class,
            'blog_author'      => \App\Models\Blog\BlogAuthor::class,
            'store_info_page'  => \App\Models\Store\StoreInfoPage::class,
            'attribute_value'  => \App\Models\Catalog\AttributeValue::class,
            'option_value'     => \App\Models\Catalog\OptionValue::class,
        ]);
    }
}
