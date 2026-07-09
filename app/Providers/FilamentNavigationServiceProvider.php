<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\ServiceProvider;

class FilamentNavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // no-op
    }

    public function boot(): void
    {
        // Register navigation when Filament is serving the panel.
        Filament::serving(function (): void {
            // Keep existing app navigation intact; this only affects Filament's sidebar.
            Filament::registerNavigationItems([
                NavigationItem::make('Dashboard')
                    ->icon('heroicon-o-home')
                    ->url(route('filament.dashboard'))
                    ->sort(0),

                NavigationItem::make('Company Announcements')
                    ->icon('heroicon-o-megaphone')
                    ->url(route('admin.announcements.index'))
                    ->sort(10),

                NavigationItem::make('Projects')
                    ->icon('heroicon-o-briefcase')
                    ->url(route('projects.index'))
                    ->sort(20),

                NavigationItem::make('Documents')
                    ->icon('heroicon-o-document')
                    ->url(route('documents.index'))
                    ->sort(30),

                NavigationItem::make('Reports')
                    ->icon('heroicon-o-chart-bar')
                    ->url(route('reports.financial.index'))
                    ->sort(40),
            ]);
        });
    }
}
