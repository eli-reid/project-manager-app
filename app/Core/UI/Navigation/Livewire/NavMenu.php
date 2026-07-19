<?php

declare(strict_types=1);

namespace App\Core\UI\Navigation\Livewire;

use App\Core\UI\Navigation\Services\NavigationManager;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

final class NavMenu extends Component
{
    public array $sections = [];

    public string $variant = 'default';

    public function mount(NavigationManager $navigationManager, string $variant = 'default'): void
    {
        $this->variant = $variant;
        $this->sections = $navigationManager->resolve();

        // compute active states and render icon blades for Flux icons
        $currentRoute = Route::currentRouteName();
        $currentUrl = url()->current();

        foreach ($this->sections as &$section) {
            $sectionActive = false;

            foreach ($section['groups'] as &$group) {
                $groupActive = false;

                if (! empty($group['icon']) && view()->exists("flux.icon.{$group['icon']}")) {
                    try {
                        $group['icon_html'] = view("flux.icon.{$group['icon']}", ['variant' => 'micro'])->render();
                    } catch (\Throwable $e) {
                        $group['icon_html'] = null;
                    }
                } else {
                    $group['icon_html'] = null;
                }

                foreach ($group['items'] as &$item) {
                    $item['active'] = $this->isItemActive($item, $currentRoute, $currentUrl);

                    if (! empty($item['icon']) && view()->exists("flux.icon.{$item['icon']}")) {
                        try {
                            $item['icon_html'] = view("flux.icon.{$item['icon']}", ['variant' => 'micro'])->render();
                        } catch (\Throwable $e) {
                            $item['icon_html'] = null;
                        }
                    } else {
                        $item['icon_html'] = null;
                    }

                    if ($item['active']) {
                        $groupActive = true;
                        $sectionActive = true;
                    }
                }

                $group['active'] = $groupActive;
            }

            foreach ($section['items'] as &$item) {
                $item['active'] = $this->isItemActive($item, $currentRoute, $currentUrl);

                if (! empty($item['icon']) && view()->exists("flux.icon.{$item['icon']}")) {
                    try {
                        $item['icon_html'] = view("flux.icon.{$item['icon']}", ['variant' => 'micro'])->render();
                    } catch (\Throwable $e) {
                        $item['icon_html'] = null;
                    }
                } else {
                    $item['icon_html'] = null;
                }

                if ($item['active']) {
                    $sectionActive = true;
                }
            }

            $section['active'] = $sectionActive;
        }
    }

    private function isItemActive(array $item, ?string $currentRoute, string $currentUrl): bool
    {
        if (! empty($item['route']) && $currentRoute !== null) {
            if ($item['route'] === $currentRoute) {
                return $this->matchesActiveQuery($item);
            }

            if (str_starts_with($currentRoute, (string) $item['route'])) {
                return $this->matchesActiveQuery($item);
            }
        }

        if (! empty($item['url'])) {
            $itemUrl = (string) $item['url'];
            if ($itemUrl === $currentUrl) {
                return $this->matchesActiveQuery($item);
            }

            $path = parse_url($itemUrl, PHP_URL_PATH) ?: '';
            $currentPath = parse_url($currentUrl, PHP_URL_PATH) ?: '';
            if ($path !== '' && str_starts_with($currentPath, $path)) {
                return $this->matchesActiveQuery($item);
            }
        }

        return false;
    }

    private function matchesActiveQuery(array $item): bool
    {
        $activeQuery = $item['meta']['active_query'] ?? null;

        if (! is_array($activeQuery) || $activeQuery === []) {
            return true;
        }

        $defaultQuery = $item['meta']['default_query'] ?? [];

        foreach ($activeQuery as $key => $expectedValue) {
            $actualValue = request()->query($key);

            if (($actualValue === null || $actualValue === '') && array_key_exists($key, $defaultQuery)) {
                $actualValue = $defaultQuery[$key];
            }

            if ((string) $actualValue !== (string) $expectedValue) {
                return false;
            }
        }

        return true;
    }

    public function render()
    {
        if ($this->variant === 'dashboard' && view()->exists('core-navigation::livewire.dashboard-sidebar-flux')) {
            return view('core-navigation::livewire.dashboard-sidebar-flux', ['sections' => $this->sections]);
        }

        // prefer Flux-based sidebar in core resources, otherwise fall back to simple menu
        if (view()->exists('core-navigation::livewire.sidebar-flux')) {
            return view('core-navigation::livewire.sidebar-flux', ['sections' => $this->sections]);
        }

        if (view()->exists('core-navigation::livewire.sidebar')) {
            return view('core-navigation::livewire.sidebar', ['sections' => $this->sections]);
        }

        // fallback to global resource view if core namespaced views are not present
        if (view()->exists('livewire.navigation.sidebar-flux')) {
            return view('livewire.navigation.sidebar-flux', ['sections' => $this->sections]);
        }

        return view('livewire.navigation.menu', ['sections' => $this->sections]);
    }
}
