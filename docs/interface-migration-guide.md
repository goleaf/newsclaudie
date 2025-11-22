# Interface Migration Guide

This guide explains how to adopt the refreshed UI primitives and pagination helpers introduced in the audit.

## Target Audience

- Blade authors moving legacy list views to the shared pagination widget.
- Backend engineers exposing new paginated endpoints.
- Front-end contributors extending the typed Alpine helpers.

## Highlights

1. **Unified pagination widget** – use `<x-ui.pagination>` instead of hand-rolled summaries/links.
2. **Page-size helper** – clamp and normalise query parameters via `App\Support\Pagination\PageSize` (including the shared `queryParam()` name).
3. **Typed front-end bootstrap** – `resources/js/app.ts` now expects TypeScript-safe DOM interactions.
4. **Comment threads** – `<x-comments.list>` accepts the same pagination props and preserves the `#comments` anchor when switching sizes.

## Adopting `<x-ui.pagination>`

1. Inject a paginator into your Blade view (controller or Livewire).  
2. Replace manual `links()` calls with:

```blade
<x-ui.pagination
    :paginator="$paginator"
    summary-key="posts.pagination_summary"
    :per-page-options="$options"
    :show-per-page="true"
/>
```

3. Optional props:
   - `variant="inline"` for table toolbars.
   - `per-page-mode="livewire"` plus `per-page-field` binding for Livewire state.
   - `per-page-form-action="{{ request()->url().'#anchor' }}"` when the selector should maintain an anchor (e.g. comments).

## Normalising Query Parameters

Use the helper for any controller that exposes the shared `per_page` parameter:

```php
use App\Support\Pagination\PageSize;

$param = PageSize::queryParam(); // defaults to "per_page"
$options = PageSize::options([12, 24, 36], 12);
$perPage = PageSize::resolve($request->integer($param), $options, 12);
```

Pass `$options` (and optionally `$param`) to the view so the component can render the same dropdown.

## Front-end Typing

- `resources/js/app.ts` now declares `window.Alpine` and narrows all DOM queries.  
- When adding new DOM hooks: type selectors (`document.querySelector<HTMLButtonElement>(...)`) and guard dynamic lookups with helper refinements.
- Run `npm run typecheck` to ensure the TypeScript compiler remains happy.

## Translation Keys

- Shared summary string: `pagination.summary` (plus collection-specific overrides like `posts.pagination_summary`).  
- Page-size label: `pagination.per_page_label`.  
- Validation for unsupported page sizes: `validation.posts.per_page_options`.

## Compatibility Notes

- URLs using the old `per_page` parameter continue to work; unsupported values fall back to the default option.
- The legacy `resources/views/layouts/admin.blade.php` now proxies to the component layout, so no template changes are required for existing `@extends` usage.


