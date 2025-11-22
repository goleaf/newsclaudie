# Interface Audit & Refactoring Plan

This document tracks the requested interface overhaul, the associated checklist, and the completed remediation work. Updated after the latest audit to keep pagination, comment threads, and documentation aligned with the shared UI primitives.

## Audit Checklist

- [x] Interface architecture review
- [x] Component optimization & hierarchy
- [x] Duplicate detection & elimination
- [x] Pagination implementation across lists/tables
- [x] Refactoring & state/data improvements
- [x] Solution hardening (errors, accessibility, performance)
- [x] Documentation & migration guidance

## Current Findings (2025-11-22)

- Pagination primitives mixed `pageSize*` and `perPage*` props and kept hard-coded `per_page` defaults, forcing Volt screens to repeat field names and option lists.
- Admin/data tables exposed per-page selectors even when callers forgot to provide a paginator, and summaries could drift when callers hand-built strings instead of relying on `ui.pagination.*`.
- Migration guidance lagged behind the component defaults, encouraging redundant props in Livewire screens and outdated references to `ManagesPerPage` contexts.
- Translation usage still mixes `ui.pagination.*` and `pagination.*`, risking drift as new locales arrive.

## Remediation (2025-11-22)

- Defaulted `<x-ui.pagination>`, `<x-ui.table>`, `<x-ui.data-table>`, and `<x-admin.table>` to `PageSize::queryParam()` with sanitised `perPageMode` handling so Livewire + HTTP screens inherit the same field automatically.
- Simplified Volt admin tables to rely on config-driven option sets and the built-in `ui.pagination.summary` copy—no hand-written per-page props required in the views.
- Refreshed both migration guides to highlight component defaults, `perPageContext()` hooks, and `perPageOptions` naming (instead of mixed pageSize/perPage aliases).
- Documented the preferred `ui.pagination.*` keys and fallbacks so future locales stay aligned.

## Completed Improvements (2025-11-22)

### Interface & Component Architecture
- Added `App\Livewire\Concerns\ManagesPerPage`, giving every Volt-powered admin screen a shared, sanitized `$perPage` workflow (query-string persistence, `resetPage()` hooks, overridable option lists).
- Rebuilt `x-admin.table` (plus `<x-admin.table-head>`) on top of the shared `x-ui.table` foundation, keeping toolbar slots, consistent table scaffolding, and embedded pagination in sync across admin screens.
- Extended `<x-comments.list>` so the public post detail view uses the same pagination/row components as admin lists, including optional inline moderation actions.
- **Unified `<x-ui.pagination>`** – the component renders a single, accessible control set for Blade and Livewire contexts, exposes `aria-label` strings, preserves nested query params, and deduplicates the previous template variants that lived inside `resources/views/components/ui/pagination.blade.php`.

### Pagination & UX
- Centralized all pagination UI via `x-ui.pagination`, which now powers admin, public, and Livewire contexts with the same summary copy, per-page selector, Livewire/HTTP behaviors, and ARIA labels.
- Per-page selectors (with validated server-side defaults) are available on posts, categories, category detail feeds, admin resources, and on-post comment threads.
- Admin `<x-admin.table>` defaults to Livewire mode with config-backed page-size options, so Volt screens no longer need to pass manual summary or per-page props.
- Comment pagination honours `comments_per_page` and preserves anchors via `CommentPageSize::locatePage()` so readers return to the correct thread page after actions.

### Data & State Management
- Consolidated the `App\Support\Pagination\PageSize` helper so HTTP controllers, Livewire traits, and context-based defaults (`config/interface.php`) all share the same resolver + `queryParam()`.
- Standardized on `PageSize::queryParam()` for the `per_page` query string, eliminating magic strings across controllers, FormRequests, and Blade views.
- Added `App\Support\Pagination\CommentPageSize` so comment lists share the same defaults/options/anchors across controllers and Blade components.
- Translations rely on the `ui.pagination.*` namespace (`summary`, `per_page`, `aria_label`) while retaining legacy `pagination.*` fallbacks for compatibility.

## Migration / Usage Guidance

1. **Livewire lists** should `use ManagesPerPage;`, expose `perPage` in `$queryString` with `PageSize::contextDefault('admin')`, and render `<x-admin.table :pagination="$collection" />` (defaults to Livewire mode + config options). Override `perPageContext()` or `per-page-mode` only when deviating from the admin presets.
2. **Blade/HTTP lists** can rely on `<x-ui.pagination>` or `<x-ui.data-table>`; both default to `PageSize::queryParam()` and `ui.pagination.summary`, so only set `per-page-field` or `summary-key` for bespoke contexts. Preserve active filters via hidden inputs (see `post.index`).
3. **Comments** use the `comments_per_page` query parameter (via `CommentPageSize::queryParam()`). Any script or deep link that expects a non-default page size must carry this param (and include `#comments` if the anchor behavior is desired).
4. **Translations**: prefer `ui.pagination.*` for new work; keep JSON/PHP locales aligned and minimise reliance on the legacy `pagination.*` keys.

## Residual Risks / Follow-ups

- Keep an eye on custom pagination consumers (e.g., bespoke data exports) to ensure they pick up the shared component rather than rolling their own UI.
- When adding future Volt screens, wire them through `ManagesPerPage` immediately—this avoids the need for bespoke query-string plumbing later.
- Sweep remaining locales for stray `pagination.*` vs `ui.pagination.*` strings to keep component fallbacks tidy as new languages land.
- Legacy Jetstream dashboard still carries bespoke cards/tables without shared pagination; migrate or formally retire it before adding features.
- Duplicate interface docs (upper/lowercase variants) still exist—merge them into a single canonical path to prevent guidance drift.

_Last updated: 2026-02-16_
