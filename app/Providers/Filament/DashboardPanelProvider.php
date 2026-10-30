<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\DashboardStatsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
// use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DashboardPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn (): HtmlString => new HtmlString(<<<'HTML'
                <style>
                    .ticket-main-panel,
                    .ticket-main-panel > .fi-section {
                        height: 100%;
                    }

                    .ticket-side-panels,
                    .ticket-side-panels > .fi-grid {
                        height: 100%;
                    }

                    .ticket-side-panels > .fi-grid {
                        grid-auto-rows: 1fr;
                    }

                    .ticket-side-panels .fi-grid-col,
                    .ticket-side-panels .fi-sc-component,
                    .ticket-side-panels .fi-sc-section,
                    .ticket-side-panels .fi-section {
                        height: 100%;
                    }

                    .ticket-chat {
                        display: flex;
                        flex-direction: column;
                        gap: 0.75rem;
                        padding: 1.25rem 1.5rem;
                    }

                    .ticket-chat-message {
                        display: flex;
                        flex-direction: column;
                        align-items: flex-start;
                    }

                    .ticket-chat-message.is-mine {
                        align-items: flex-end;
                    }

                    .ticket-chat-bubble {
                        max-width: min(42rem, 85%);
                        padding: 0.625rem 0.875rem;
                        border-radius: 0.75rem;
                        border-end-start-radius: 0.25rem;
                        background-color: var(--gray-100);
                    }

                    .dark .ticket-chat-bubble {
                        background-color: var(--gray-800);
                    }

                    .ticket-chat-message.is-mine .ticket-chat-bubble {
                        border-end-start-radius: 0.75rem;
                        border-end-end-radius: 0.25rem;
                        background-color: var(--primary-50);
                    }

                    .dark .ticket-chat-message.is-mine .ticket-chat-bubble {
                        background-color: var(--primary-950);
                    }

                    .ticket-chat-meta {
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                        margin-bottom: 0.25rem;
                        font-size: 0.75rem;
                        line-height: 1rem;
                        color: var(--gray-500);
                    }

                    .ticket-chat-sender {
                        font-weight: 600;
                        color: var(--gray-950);
                    }

                    .dark .ticket-chat-sender {
                        color: var(--gray-50);
                    }

                    .ticket-chat-body {
                        font-size: 0.875rem;
                        line-height: 1.5rem;
                        color: var(--gray-900);
                        overflow-wrap: anywhere;
                        white-space: pre-line;
                    }

                    .dark .ticket-chat-body {
                        color: var(--gray-100);
                    }

                    .ticket-chat-composer {
                        display: flex;
                        align-items: flex-end;
                        gap: 0.5rem;
                        padding: 1rem 1.5rem 1.5rem;
                    }

                    .ticket-chat-composer-field {
                        flex: 1;
                    }

                    .ticket-chat-composer-input {
                        display: block;
                        width: 100%;
                        padding: 0.625rem 0.875rem;
                        border: 1px solid var(--gray-300);
                        border-radius: 0.75rem;
                        background-color: transparent;
                        font-size: 0.875rem;
                        line-height: 1.25rem;
                        color: var(--gray-950);
                        resize: vertical;
                    }

                    .ticket-chat-composer-input::placeholder {
                        color: var(--gray-400);
                    }

                    .ticket-chat-composer-input:focus {
                        border-color: var(--primary-500);
                        outline: 2px solid transparent;
                        outline-offset: 2px;
                    }

                    .dark .ticket-chat-composer-input {
                        border-color: var(--gray-700);
                        color: var(--gray-100);
                    }

                    .ticket-chat-composer-error {
                        margin-top: 0.375rem;
                        font-size: 0.75rem;
                        line-height: 1rem;
                        color: var(--danger-600);
                    }

                    .dark .ticket-chat-composer-error {
                        color: var(--danger-400);
                    }
                </style>
                HTML),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            fn (): HtmlString => new HtmlString(<<<'HTML'
                <script>
                    (function () {
                        function getJakartaTime() {
                            const d = new Date();
                            const formatter = new Intl.DateTimeFormat('en-GB', {
                                timeZone: 'Asia/Jakarta',
                                year: 'numeric',
                                month: '2-digit',
                                day: '2-digit',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: false,
                            });
                            const parts = formatter.formatToParts(d);
                            const map = {};
                            for (const p of parts) {
                                map[p.type] = p.value;
                            }
                            return `${map.year}-${map.month}-${map.day} ${map.hour}:${map.minute}:${map.second}`;
                        }

                        function createClockComponent() {
                            return {
                                timer: null,
                                update() {
                                    const input = this.$el;
                                    if (!input) return;
                                    const val = getJakartaTime();
                                    input.value = val;
                                    input.dispatchEvent(new Event('input', { bubbles: true }));
                                    input.dispatchEvent(new Event('change', { bubbles: true }));

                                    try {
                                        if (this.$wire && typeof this.$wire.set === 'function') {
                                            this.$wire.set('data.needed_at', val, false);
                                        }
                                    } catch (e) {}
                                },
                                init() {
                                    this.update();
                                    this.timer = setInterval(() => {
                                        this.update();
                                    }, 1000);

                                    const form = this.$el?.closest('form');
                                    if (form) {
                                        form.addEventListener('submit', () => {
                                            this.update();
                                        });
                                    }
                                },
                                destroy() {
                                    if (this.timer) {
                                        clearInterval(this.timer);
                                        this.timer = null;
                                    }
                                }
                            };
                        }

                        window.realtimeClock = createClockComponent;

                        function registerRealtimeClock() {
                            if (!window.Alpine) return;
                            try {
                                if (Alpine.data && Alpine.data('realtimeClock')) return;
                            } catch (e) {}

                            Alpine.data('realtimeClock', createClockComponent);
                        }

                        if (window.Alpine) {
                            registerRealtimeClock();
                        } else {
                            document.addEventListener('alpine:init', registerRealtimeClock);
                        }
                    })();
                </script>
                HTML),
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->navigationItems([
                NavigationItem::make('Home')
                    ->icon(Heroicon::OutlinedHome)
                    ->url(url('/'))
                    ->openUrlInNewTab()
                    ->sort(PHP_INT_MIN),
            ])
            ->id('dashboard')
            ->path('dashboard')
            ->viteTheme('resources/css/filament/dashboard/theme.css')
            ->login()
            ->brandLogo(asset('/images/company-logo.png'))
            ->brandLogoHeight('2.5rem')
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('4rem')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                DashboardStatsWidget::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Edit profile')
                    ->icon(Heroicon::OutlinedUser)
                    ->url(fn (): string => request()->user() ? UserResource::getUrl('edit', ['record' => request()->user()]) : ''),
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
