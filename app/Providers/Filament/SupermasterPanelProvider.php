<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\SetAdminLocale;
use App\Models\Store;
use Filament\Tables\Table; // Ensure this is imported
use Filament\Support\Enums\Width;

class SupermasterPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('supermaster')
            ->path('supermaster')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetAdminLocale::class, // Set admin locale
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->unsavedChangesAlerts() // Alert on unsaved changes
            ->databaseTransactions() // Not sure if this works, needs test under PostgreSQL
            ->tenant(Store::class)   // Connect panel context to store: data, that belongs to single store will be changed according to current selected store
            ->brandName('') // Set brand name
            // Setpagination preferences
            ->bootUsing(function () {
                Table::configureUsing(function (Table $table) {
                    $table
                        ->paginated([50, 100, 200]) // Set the available "items per page" dropdown options globally
                        ->defaultPaginationPageOption(50); // Set the default option selected initially
                });
            })
            ->sidebarCollapsibleOnDesktop() // Set admin main menu to collapsible
            ->maxContentWidth(Width::Full)  // Set main page content to fill width
            ;
    }
}
