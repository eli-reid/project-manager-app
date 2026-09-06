# Plans Domain - Mobile UI

Mobile is where plan viewing actually happens - a superintendent standing in a corridor checking a
detail. It is a first-class surface, not a responsive afterthought.

Decision: **online-only.** No offline sheet pinning in v1.

## Conventions

Matching `app/Domains/Projects/Livewire/Mobile/Projects/Index.php`:

- Components in `app/Domains/Plans/Livewire/Mobile/Sheets/`.
- Views in `app/Domains/Plans/Resources/Views/livewire/mobile/sheets/`.
- `#[Layout('layouts.mobile')]` and `#[Title(...)]` attributes.
- Routes in `app/Domains/Plans/Routes/mobile.php`, registered with
  `['web', 'auth', 'verified']` middleware.
- Redirect mapping via the `RegistersMobileRedirectMappings` trait:

```php
$this->registerMobileRoutePrefixMapping('plans.', 'plans.mobile.');
```

The mobile layout supplies the sticky header, back button, safe-area padding, and dark theme
(`bg-zinc-950`, `text-zinc-50`).

## `Livewire/Mobile/Sheets/Index`

- Single-column list of large tappable rows: thumbnail on the left, sheet number and title on the
  right, revision and markup badges beneath.
- Sticky search field at the top, debounced 300ms, searching number and title.
- Horizontally scrolling discipline chips below search.
- Infinite scroll with `wire:intersect`.
- `data-mobile-haptic` on row taps and primary actions, matching existing mobile screens.
- Minimum 44px touch targets throughout.

## `Livewire/Mobile/Sheets/Viewer`

Full-bleed viewer. Chrome auto-hides on interaction and returns on tap.

Gestures:

| Gesture | Action |
| --- | --- |
| Pinch | Zoom toward the pinch midpoint |
| Drag | Pan |
| Double tap | Toggle fit-page and 2x zoom at that point |
| Swipe left / right | Previous / next sheet, only when zoomed to fit |
| Long press | Open the markup tool sheet |

The swipe-only-when-fit rule matters. Once zoomed in, horizontal drag must pan rather than change
sheets, or the viewer feels broken.

Implementation uses Pointer Events with `touch-action: none` on the stage so the browser does not
steal the gesture. Two-pointer tracking computes scale and midpoint; single-pointer drags pan.
Momentum is not simulated - direct manipulation feels more precise for drawings.

Bottom toolbar as a compact bar that expands into a bottom sheet:

- Sheet jump, markup tools, revision selector, layers toggle, download.
- Reduced markup set versus desktop: pen, arrow, text, cloud. Measure, stamp, and ellipse are
  desktop-only in v1.

Top chrome shows sheet number, title, and a revision pill.

## Performance On Mobile

- Load the thumbnail first, then preview. On a slow connection the user gets something legible
  immediately.
- Preload only the immediate next and previous sheet, not a window of five - mobile data matters.
- Cap the preview to the device pixel budget: requesting a 2000px-wide image for a 390px viewport
  wastes bandwidth. Serve a mid-size derivative on small viewports via a variant parameter.
- Annotation canvas is sized to CSS pixels times `devicePixelRatio`, capped at 2 to avoid
  enormous buffers on high-DPI phones.

## Service Worker

**No changes to `public/sw.js`.**

Repo memory records two hard-won constraints:

1. Navigation HTML must not be cached, because stale CSRF tokens caused 419 errors on mobile
   login and dashboard flows.
2. `offline.html` must only be served for navigation requests. Returning it for asset or module
   requests caused MIME type errors.

Plan derivatives are private, authorized, per-project images. Caching them in the shared service
worker cache would risk serving one project's drawings after a user switches context. Deferred
along with offline pinning.

## Mobile-Specific Tests

- Livewire component tests for the mobile index and viewer.
- Route prefix mapping resolves `plans.sheets.show` to `plans.mobile.sheets.show`.
- Pest browser tests on an iPhone viewport asserting no JavaScript errors, that pinch zoom changes
  the transform, and that swipe navigates only when zoomed to fit.
