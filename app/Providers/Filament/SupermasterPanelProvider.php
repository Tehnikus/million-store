<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Tenancy\StoreWizard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
// use Filament\Widgets\AccountWidget;
// use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\SetAdminLocale;
use App\Models\Global\Store;
use Filament\Tables\Table;
use Filament\Support\Enums\Width;
use Filament\Actions\Action;
use Spatie\Translatable\Facades\Translatable;

class SupermasterPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        Translatable::fallback(
            fallbackAny: true,
        );

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
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
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
                SetAdminLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->unsavedChangesAlerts() // Alert on unsaved changes
            ->databaseTransactions() // Not sure if this works, needs test under PostgreSQL
            ->brandName('')          // Set brand name
            ->tenant(Store::class)   // Connect panel context to store: data, that belongs to single store will be changed according to current selected store
            // Setpagination preferences
            ->bootUsing(function () {
                Table::configureUsing(function (Table $table) {
                    $table
                        ->paginated([50, 100, 200]) // Set the available "items per page" dropdown options globally
                        ->defaultPaginationPageOption(50) // Set the default option selected initially
                        ->modifyUngroupedRecordActionsUsing(fn (Action $action) => $action->iconButton());
                });
            })
            ->sidebarCollapsibleOnDesktop() // Set admin main menu to collapsible
            ->maxContentWidth(Width::Full)  // Set main page content to fill width
            ->resourceCreatePageRedirect('index') // Redirect on resource create
            ->resourceEditPageRedirect('index') // Redirect on resource edit
            ->tenantRegistration(StoreWizard::class) // Store creation wizard on fresh migration
            // ->simplePageMaxContentWidth(Width::FitContent) // Login AND store tenant creation page
            ->subNavigationPosition(SubNavigationPosition::Top)
            ;
    }
}
