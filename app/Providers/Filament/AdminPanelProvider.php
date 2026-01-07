<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EmailVerification;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Registration;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\MyCustomProfile;
use App\Filament\Resources\MenuResource;
use App\Filament\Resources\NavigationResource;
use App\Http\Middleware\EnsureActiveRole;
use App\Http\Middleware\EnsureUserUnit;
use App\Http\Middleware\FilamentRobotsMiddleware;
use App\Http\Middleware\PreventCustomerAccess;
use App\Livewire\MyProfileExtended;
use App\Settings\GeneralSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Pages\Auth\Register;
use Filament\View\LegacyComponents\Widget;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            // ->unsavedChangesAlerts()
            ->spa()
            ->spaUrlExceptions(fn(): array => [
                // url('/admin/manage-site-script'),
                // NavigationResource::getUrl('index'),
                url('/admin/login'),
                url('/admin/password-reset/request'),
                url('/admin/register'),
            ])
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class)
            ->emailVerification(EmailVerification::class)
            // ->registration(Registration::class) // Disabled: Signup is handled on frontend
            ->favicon(function () {
                try {
                    $settings = app(GeneralSettings::class);
                    return empty($settings->site_favicon) ? 'https://placehold.co/50.jpeg?text=No\nImage' : Storage::url($settings->site_favicon);
                } catch (\Exception $e) {
                    return 'https://placehold.co/50.jpeg?text=No\nImage';
                }
            })
            ->brandName(function () {
                try {
                    return app(GeneralSettings::class)->brand_name;
                } catch (\Exception $e) {
                    return 'Admin Panel';
                }
            })
            ->brandLogo(function () {
                try {
                    $settings = app(GeneralSettings::class);
                    return empty($settings->brand_logo) ? 'https://placehold.co/240x50.jpeg?text=No%20Image' : Storage::url($settings->brand_logo);
                } catch (\Exception $e) {
                    return 'https://placehold.co/240x50.jpeg?text=No%20Image';
                }
            })
            ->darkModeBrandLogo(function () {
                try {
                    $settings = app(GeneralSettings::class);
                    return empty($settings->brand_logo_dark) ? 'https://placehold.co/240x50.jpeg?text=No%20Image' : Storage::url($settings->brand_logo_dark);
                } catch (\Exception $e) {
                    return 'https://placehold.co/240x50.jpeg?text=No%20Image';
                }
            })
            ->brandLogoHeight(function () {
                try {
                    return app(GeneralSettings::class)->brand_logoHeight . 'px';
                } catch (\Exception $e) {
                    return '50px';
                }
            })
            ->colors(function () {
                try {
                    return app(GeneralSettings::class)->site_theme;
                } catch (\Exception $e) {
                    return [];
                }
            })
            ->databaseNotifications()->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                // Navigation\NavigationGroup::make()
                //     ->label('Master Aset'),
                Navigation\NavigationGroup::make()
                    ->label('Master Data'),
                // Navigation\NavigationGroup::make()
                //     ->label('Master Unit'),
                // Navigation\NavigationGroup::make()
                //     ->label('Booking Management'),
                Navigation\NavigationGroup::make()
                    ->label('Banner'),
                Navigation\NavigationGroup::make()
                    ->label('Organization')
                    ->collapsible(false),
                Navigation\NavigationGroup::make()
                    ->label(__('menu.nav_group.content'))
                    ->collapsible(),
                Navigation\NavigationGroup::make()
                    ->label(__('menu.nav_group.blog'))
                    ->collapsible(false),
                Navigation\NavigationGroup::make()
                    ->label(__('menu.nav_group.access'))
                    ->collapsed(),
                Navigation\NavigationGroup::make()
                    ->label(__('menu.nav_group.sites'))
                    ->collapsed(false),
                Navigation\NavigationGroup::make()
                    ->label(__('menu.nav_group.settings')),
                Navigation\NavigationGroup::make()
                    ->label(__('menu.nav_group.activities')),

            ])
            ->navigationItems([
                Navigation\NavigationItem::make('Log Viewer') // !! To-Do: lang
                    ->visible(
                        function (): bool {
                            /** @var \App\Models\User $user */
                            $user = Auth::user();
                            return $user->can('access_log_viewer');
                        }
                    )
                    ->url(config('app.url') . '/' . config('log-viewer.route_path'), shouldOpenInNewTab: true)
                    ->icon('fluentui-document-bullet-list-multiple-20-o')
                    ->group(__('menu.nav_group.activities'))
                    ->sort(99),
            ])
            ->userMenuItems([
                'logout' => MenuItem::make()->color('danger'),
                // ...
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources([
                config('filament-logger.activity_resource')
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            // ->widgets([
            //     Widgets\FilamentInfoWidget::class,
            //     Widgets\AccountWidget::class
            // ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                FilamentRobotsMiddleware::class,
                PreventCustomerAccess::class,
                // EnsureActiveRole::class
                // EnsureUserUnit::class
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                // \CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin::make(),
                // \pxlrbt\FilamentSpotlight\SpotlightPlugin::make(),
                \TomatoPHP\FilamentMediaManager\FilamentMediaManagerPlugin::make()
                    ->allowSubFolders()
                    ->allowUserAccess(),
                \BezhanSalleh\FilamentExceptions\FilamentExceptionsPlugin::make(),
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 2,
                        'sm' => 1
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
                \Jeffgreco13\FilamentBreezy\BreezyCore::make()
                    ->myProfileComponents([
                        'personal_info' => MyProfileExtended::class,
                    ])
                    ->customMyProfilePage(MyCustomProfile::class)
                    ->myProfile(
                        shouldRegisterUserMenu: false,
                        shouldRegisterNavigation: true,
                        navigationGroup: 'Settings',
                        hasAvatars: true,
                        slug: 'my-profile'
                    ),
                \DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin::make()
                    ->showEmptyPanelOnMobile(false)
                    ->formPanelPosition('right')
                    ->formPanelWidth('40%')
                    ->emptyPanelBackgroundImageOpacity('80%')
                    ->emptyPanelBackgroundImageUrl(
                        (function (): string {
                            try {
                                if (Schema::hasTable('settings')) {
                                    $settings = app(GeneralSettings::class);
                                    if (!empty($settings->login_cover_image)) {
                                        return Storage::url($settings->login_cover_image);
                                    }
                                }
                            } catch (\Exception $e) {
                                // Database connection error, use default image
                            }
                            return 'https://placehold.co/1620x1080.jpeg';
                        })()
                    ),
                \ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin::make()
                    ->usingPage(\App\Filament\Pages\Backup::class)
                    ->usingQueue('backups'),
                \Rupadana\ApiService\ApiServicePlugin::make(),
            ]);
    }
}
