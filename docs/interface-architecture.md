# Interface Architecture Report

## Snapshot

| Area | Prior State | New Structure |
| --- | --- | --- |
| Pagination | Multiple bespoke summaries, inconsistent per-page knobs | Shared `x-ui.pagination` wrapper + `PageSize` helper across posts, categories, comments, admin tables |
| Layouts | Duplicate admin templates under `layouts/` and `components/layouts/` | Single source of truth (`x-layouts.admin`), legacy layout now proxies to the component |
| Utilities | Controllers manually clamped `paginate()` values | Livewire Volt pages + `ManagesPerPage` call `PageSize` so the per-page rules live in one place |
| Front-end runtime | Untyped Alpine bootstrapper, implicit `window.Alpine` | Typed `resources/js/app.ts` (module-aware, DOM-safe helpers, explicit global contract) |

## Key Findings & Fixes

1. **Duplicate admin layout code**  
   - *Finding:* `resources/views/layouts/admin.blade.php` duplicated the Livewire layout markup but with outdated navigation links.  
   - *Resolution:* The file now proxies to `<x-layouts.admin>` so the component in `resources/views/components/layouts/admin.blade.php` remains the single source.

2. **Inconsistent pagination UI**  
   - *Finding:* Public listings (posts, categories, category detail) each rendered their own summary copy and `links()` call.  
   - *Resolution:* All entry points now defer to `x-ui.pagination`, guaranteeing shared markup, screen reader copy, and optional page-size selectors.

3. **Unsafe per-page inputs**  
   - *Finding:* Controllers trusted raw `per_page` query strings and used ad hoc option sets.  
   - *Resolution:* `PageSize::options()` + `PageSize::resolve()` (see `app/Support/Pagination/PageSize.php`) normalise values and ensure defaults are always present.

4. **Missing documentation**  
   - *Finding:* No artifact described component hierarchy nor pagination contracts.  
   - *Resolution:* This report plus `docs/interface-migration-guide.md` document the architecture and forward expectations.

5. **Limited type coverage in the JS entry**  
   - *Finding:* `resources/js/app.ts` (previously `.js`) relied on implicit `any` access, causing editor noise and runtime guards sprinkled throughout the Blade templates.  
   - *Resolution:* The module now declares the `window.Alpine` contract, narrows DOM query selectors, and reuses helper guards for form fields.

## Component Hierarchy (Shared UI)

- `x-ui.pagination` – handles summaries, page-size selectors (HTTP + Livewire modes), and window link density. Consumed by:
  - `resources/views/livewire/posts/index.blade.php`
  - `resources/views/livewire/categories/index.blade.php`
  - `resources/views/livewire/category-posts.blade.php`
  - `resources/views/livewire/post/comments.blade.php`
  - `x-admin.table` (admin datasets)
- `App\Support\Pagination\PageSize` – shared resolver for Livewire components and helpers:
  - Volt `posts.index` & `categories.index` pages (`ManagesPerPage`)
  - Volt `categories.show` via `livewire/category-posts`
  - `CommentPageSize` helper for per-thread pagination

## Pagination Coverage Matrix

| Surface | Strategy | Page-size options | File(s) |
| --- | --- | --- | --- |
| Public posts index | Volt full-page Livewire (`?per_page=*`) | 12 · 18 · 24 · 36 | `Volt::route('posts', 'posts.index')`, `resources/views/livewire/posts/index.blade.php` |
| Public categories index | Volt full-page Livewire (`?per_page=*`, search) | 12 · 15 · 24 · 30 | `Volt::route('categories', 'categories.index')`, `resources/views/livewire/categories/index.blade.php` |
| Category detail posts | Livewire child paginator on Volt page | 9 · 12 · 18 · 24 | `Volt::route('categories/{category}', 'categories.show')`, `resources/views/livewire/category-posts.blade.php` |
| Post comments | Config-driven per-page, Livewire paginator | Config (`blog.commentsPerPage`) | `resources/views/livewire/post/comments.blade.php` |
| Admin data tables | Livewire paginators wrapped by `x-admin.table` → `x-ui.pagination` | Component-specific (defaults to Livewire per-page) | `resources/views/components/admin/table.blade.php` |

## Front-end Runtime Notes

- `resources/js/app.ts` is now type-safe, with dedicated guards for form field lookups and deterministic dark-mode toggling.
- `package.json` already included `typescript` and exports `npm run typecheck`; this script now validates the stricter typings added above.

## Follow-ups

- Consider surfacing `perPageMode="livewire"` controls in admin tables once backend handlers support dynamic page-size updates.
- Run `npm run typecheck` (already added) inside CI to keep Alpine helpers type-safe as new UI affordances land.

