# Dashboard Domain Conventions

This note documents dashboard ownership boundaries in `project-manager-app`.

## Route Ownership

Dashboard routes are owned by the dashboard domain and registered from:
- `app/Core/Dashboard/Routes/web.php`

The route registration is wired in:
- `app/Core/Dashboard/Providers/DashboardServiceProvider.php`

Route names remain unchanged:
- `dashboard`
- `mobile.dashboard`

## View Ownership

Dashboard page views are domain-owned and loaded via the `dashboard::` namespace from:
- `app/Core/Dashboard/Resources/Views/index.blade.php`
- `app/Core/Dashboard/Resources/Views/mobile/index.blade.php`

Shared cross-domain layouts remain in app resources:
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/mobile.blade.php`

## Widget Composition

Dashboard section composition and visibility filtering remain centralized in:
- `app/Core/Dashboard/Providers/DashboardServiceProvider.php`

Domain widgets continue to self-register through `DashboardWidgetRegistry` and should not hardcode route/view assumptions outside their own domain.
