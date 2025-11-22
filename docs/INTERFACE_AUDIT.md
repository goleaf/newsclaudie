# Interface Audit & Refactoring Plan

This document tracks the requested interface overhaul, the associated checklist, and the completed remediation work.

## Audit Checklist

- [x] Interface architecture review
- [x] Component optimization & hierarchy
- [x] Duplicate detection & elimination
- [x] Pagination implementation across lists/tables
- [x] Refactoring & state/data improvements
- [x] Solution hardening (errors, accessibility, performance)
- [x] Documentation & migration guidance

## Completed Improvements

### Interface & Component Architecture
- Added `App\Livewire\Concerns\ManagesPerPage`, giving every Volt-powered admin screen a shared, sanitized `$perPage` workflow (query-string persistence, `resetPage()` hooks, overridable option lists).
- Rebuilt `x-admin.table` to expose toolbar slots, consistent card chroming, and embedded pagination summaries while keeping row/empty components laser-focused on table cells.
- Extended `<x-comments.list>` so the public post detail view uses the same pagination/row components as admin lists, including optional inline moderation actions.
- **Unified `<x-ui.pagination>`** – the component now renders a single, accessible control set for Blade and Livewire contexts, exposes `aria-label` strings, and deduplicates the three previous template variants that lived inside `resources/views/components/ui/pagination.blade.php`.

### Pagination & UX
- Centralized all pagination UI via `x-ui.pagination`, which now powers admin, public, and Livewire contexts with the same summary copy, per-page selector, Livewire/HTTP behaviors, and ARIA labels.
- Added per-page selectors (with validated server-side defaults) to posts, categories, category detail feeds, admin resources, and on-post comment threads.
- Surfaced range summaries and totals for every paginated collection, replacing scattered `links()` calls and bespoke copy blocks.

### Data & State Management
- Consolidated the `App\Support\Pagination\PageSize` helper so HTTP controllers, Livewire traits, and future context-based defaults (`config/interface.php`) all share the same resolver + `queryParam()`.
- Standardized on `PageSize::queryParam()` for the `per_page` query string, eliminating magic strings across controllers, FormRequests, and Blade views.
- Extended `PostController@show` + `<x-comments.list>` to honour `comments_per_page`, including anchor-aware form submissions so the browser returns to `#comments` after page-size changes.
- Translations now rely on the `ui.pagination.*` namespace (`summary`, `per_page`, `aria_label`) so both PHP + JSON locales stay in sync.

## Migration / Usage Guidance

1. **Livewire lists** should `use ManagesPerPage;` and include `'perPage' => ['except' => default]` in `$queryString`. Override `availablePerPageOptions()` if a resource needs bespoke sizes.
2. **Blade tables** should wrap content in `<x-admin.table>` and provide a localized `:summary` string when the default `ui.pagination.summary` copy is insufficient.
3. **HTTP lists** can enable the selector by passing `per-page-mode="http"` plus `per-page-field` (defaults to `PageSize::queryParam()`). Hidden inputs (see `post.index`) keep filters + page sizes in sync.
4. **Comments** use the `comments_per_page` query parameter. Any script or deep link that expects a non-default page size must carry this param (and include `#comments` if the anchor behavior is desired).
5. **Translations**: new locales only need to provide `ui.pagination.summary`, `ui.pagination.per_page_label`, `ui.pagination.aria_label`, and collection-specific summaries (e.g. `posts.pagination_summary`).

## Residual Risks / Follow-ups

- Keep an eye on custom pagination consumers (e.g., bespoke data exports) to ensure they pick up the shared component rather than rolling their own UI.
- When adding future Volt screens, wire them through `ManagesPerPage` immediately—this avoids the need for bespoke query-string plumbing later.

_Last updated: 2025-11-22_
