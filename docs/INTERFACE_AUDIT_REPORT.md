# Interface Audit Report

_Updated: 2025-11-22_

## 1. Architecture Review

- **Layout shells:** Public views share `resources/views/layouts/app.blade.php` while the Flux-powered admin uses `resources/views/components/layouts/admin.blade.php`. Both delegate typography, navigation, and footers to Blade components so that feature views stay lean.
- **Data entry points:** HTTP controllers (`PostController`, `CategoryController`) hydrate Blade templates, whereas the Livewire Volt screens (`resources/views/livewire/admin/**`) own their own queries and pagination state.
- **UI kit:** `resources/views/components/ui/**` contains the reusable surface primitives (`page-header`, `surface`, `button`, `pagination`, `data-table`). Admin-specific shells live under `resources/views/components/admin/**` and wrap Flux components.
- **TypeScript bootstrap:** `resources/js/app.ts` starts Alpine, registers small DOM helpers, and imports `resources/js/bootstrap.ts` to configure Axios.

## 2. Anti-Patterns & Resolutions

| Issue | Impact | Resolution |
| --- | --- | --- |
| Duplicate `PageSize` class definitions (two different implementations in `app/Support/Pagination/PageSize.php`). | Fatal class redeclaration risk and inconsistent behaviour between controllers. | Collapsed into a single helper with `resolve`, `resolveFromRequest`, and `options` methods. Controllers (`PostController`, `CategoryController`) and requests (`PostIndexRequest`) now share the same API. |
| Window globals declared inline inside multiple scripts. | TypeScript could not guarantee typings for `window.Alpine`/`window.axios`, reducing editor guarantees. | Centralized declarations in `resources/js/types/global.d.ts` so all modules inherit the same augmentation. |
| Duplicate validation copy for `validation.posts.per_page_options`. | Translator maintenance burden and potential runtime ambiguity. | Removed the duplicate entry from `lang/en.json`. |
| Mixed pagination contracts across Blade and Livewire. | Harder to build consistent controls and page-size selectors. | Documented and enforced the `PageSize` + `x-ui.pagination` + `ManagesPerPage` pipeline (see sections below). |

## 3. Component Optimization

- `x-ui.pagination` now serves as the single surface for pagination controls everywhere (public grids, comments, and admin tables). It exposes Livewire-friendly props (`per-page-mode="livewire"`, `per-page-field="perPage"`) and HTTP props (`per-page-mode="http"`).
- Livewire tables reuse `App\Livewire\Concerns\ManagesPerPage`, giving each Volt component a consistent way to expose allowed sizes and reset pagination when a user changes the dropdown.
- HTTP controllers rely on `PageSize::options` to keep options sorted and unique, so Blade templates can simply pass `$postPageSizeOptions` or `$categoryPageSizeOptions` into `x-ui.pagination`.

## 4. Duplicate Detection & Elimination

- Removed the legacy `interface.pagination` configuration stub and the duplicate class definition noted above.
- Normalized TypeScript globals through a single declaration file.
- Cleared the redundant validation string.

## 5. Pagination Coverage

| Surface | Data source | Pagination component | Page-size strategy |
| --- | --- | --- | --- |
| Public posts index (`resources/views/post/index.blade.php`) | `PostController@index` + `PostIndexRequest` | `<x-ui.pagination per-page-mode="http">` | Query parameter `per_page` sanitized via `PageSize`. |
| Category index/show | `CategoryController@index/show` | `<x-ui.pagination summary-key="categories.*">` | Controller constants feed into `PageSize::options`. |
| Post comments | `PostController@show` | `<x-comments.list>` delegates to `x-ui.pagination`. | Query parameter `comments_per_page`. |
| Flux admin tables | Livewire Volt components + `ManagesPerPage` trait | `<x-admin.table>` wraps `x-ui.pagination` with `per-page-mode="livewire"`. | `$perPage` public property persisted in query string. |

All lists with potentially unbounded rows now expose an accessible paginator plus a finite set of selectable page sizes.

## 6. Refactoring Strategy & Backlog

Completed in this pass:

1. **Normalize pagination helpers:** consolidate `PageSize`, surface sanitized options to Blade, and reuse inside form requests.
2. **Harmonize TypeScript globals:** rely on `.d.ts` files instead of ad-hoc declarations in each module.
3. **Audit translations:** remove duplicate validation strings.
4. **Document architecture:** publish the companion `INTERFACE_ARCHITECTURE.md` and `INTERFACE_MIGRATION.md`.

Follow-up opportunities:

- Extract the remaining bulky dashboard widgets in `resources/views/dashboard.blade.php` into dedicated Blade components so analytics tables can opt into the same summary/pagination stack if required later.
- Formalize pagination presets in configuration (e.g. `config/blog.php`) so controllers do not need to declare their own constants.
- Expand `x-ui.pagination` with slots for toolbar-level actions (filter badges, export buttons) to remove the remaining bespoke wrappers in a few screens.

## 7. Enhanced Solutions & DX Notes

- `npm run typecheck` now succeeds thanks to the centralized global typing and `.ts` bootstrap module.
- Controllers and views share a single source of truth for per-page options, preventing drift between validation, request query strings, and rendered dropdowns.
- Livewire per-page changes automatically reset pagination thanks to the `ManagesPerPage` concern.
- Detailed migration guidance plus architecture diagrams are checked into `docs/` to onboard future contributors without spelunking through the Blade tree.

# Interface Audit Report

## 1. Layout & Data Flow Map
- **Public stack** – `x-app-layout` wraps every Blade page and funnels into `resources/views/layouts/app.blade.php`, which wires navigation (`x-navigation.main`), shared alerts, and the footer. Content pages (`resources/views/post/*.blade.php`, `resources/views/categories/*.blade.php`) are thin wrappers that expect controllers to prepare view data.
- **Admin stack** – Livewire Volt routes (`routes/web.php`) mount `components.layouts.admin`, giving Flux UI shell, sidebar, and sticky header. Every Volt screen (`resources/views/livewire/admin/*/index.blade.php`) renders inside a shared `<flux:page-header>` + `<x-admin.table>` pattern.
- **Support layers** – `App\Support\Pagination\PageSize` normalizes allowed page sizes, while `App\Livewire\Concerns\ManagesPerPage` keeps Livewire query strings and dropdowns in sync. These two utilities now back both Blade and Volt experiences.

## 2. Anti-Patterns & Risks
- **Legacy dashboard divergence** – `resources/views/dashboard.blade.php` still carries Jetstream-era widgets (tables, cards, analytics) with bespoke markup and no pagination. It duplicates work already handled by Volt dashboard routes, causing bitrot and inconsistent UI.
- **Inline presentation logic** – Views such as `resources/views/post/index.blade.php` and `resources/views/categories/index.blade.php` previously recalculated filter labels, per-page defaults, and summaries inside Blade blocks, increasing coupling between markup and data shaping.
- **Pagination inconsistencies** – Several lists rendered `{{ $collection->links() }}` directly (categories, category detail) or omitted page-size controls (comment thread), leading to mismatched UX versus admin tables.

## 3. Component & Pagination Refactors
- **`x-ui.pagination` as the single source of truth** – All Blade pages now pass explicit `per-page-mode`, `per-page-field`, and validated option sets, ensuring the component can render page-size selectors only when the caller provides options. Volt tables inherit the same API via `x-admin.table`.
- **Category listings** – `resources/views/categories/index.blade.php` and `resources/views/categories/show.blade.php` now forward the sanitized option arrays provided by `CategoryController`, so both surfaces expose the same dropdown UI and honor the `per_page` query parameter automatically.
- **Comment threads** – `resources/views/post/show.blade.php` already surfaced `commentPerPageOptions`; those values now flow through `x-comments.list`, letting readers change the page size without leaving the article, while the paginator summary stays localized.

## 4. Pagination Inventory & Defaults
| Surface | Default | Options | Query Param |
| --- | --- | --- | --- |
| Public posts (`post.index`) | 12 | 12/18/24/36 | `per_page` |
| Categories index (`categories.index`) | 15 | 12/15/24/30 | `per_page` |
| Category detail (`categories.show`) | 12 | 9/12/18/24 | `per_page` |
| Article comments (`post.show`) | Config `blog.commentsPerPage` | 10/25/50/+default | `comments_per_page` |
| Volt tables (posts/categories/comments/users) | Component default (20) | Livewire trait-provided | `?perPage=` query string |

All surfaces rely on offset pagination today. Cursor pagination remains on the backlog for analytics feeds and extremely large archives but is not required for current datasets.

## 5. Enhanced Solutions & Recommendations
- **View-model discipline** – Controllers (`PostController`, `CategoryController`) now own subtitle/count/per-page calculations, reducing repeated `@php` blocks. Future work should introduce dedicated view models (or DTOs) for complex pages like dashboards.
- **Component hierarchy** – Use `x-ui.data-table` + `x-ui.pagination` for Blade tables and keep Flux-specific wrappers in `resources/views/components/admin`. This prevents dashboard templates from reimplementing table shells.
- **State management** – Livewire Volt + `ManagesPerPage` already cover admin state. On the public side, Alpine's global store (defined in `resources/js/app.ts`) should gradually replace bespoke DOM listeners when richer interactions (filters, multi-select) appear.
- **Accessibility & feedback** – The unified pagination component injects `<label>` associations and consistent focus states. Follow-up work should add skip links and announce filter changes for screen readers.

## 6. Migration Notes
1. **`<x-ui.pagination>` usage** – Always provide `per-page-mode="http"` (or `"livewire"` for Volt) when supplying page-size options. The component automatically infers the selected value via the matching query string.
2. **Categories views** – Replace raw `{{ $categories->links() }}` calls with the component and forward `$categoryPageSizeOptions` / `$categoryPostPageSizeOptions` from the controller so the dropdown reflects validated choices.
3. **Comment threads** – When embedding `<x-comments.list>`, pass `:per-page-options="$commentPerPageOptions"` and `per-page-field="comments_per_page"`. Any custom comment forms should include a hidden `comments_per_page` input if they need to preserve the reader's preference after submitting.
4. **Jetstream dashboard** – New work should migrate remaining widgets into Volt components. The legacy Blade dashboard is effectively deprecated and should not receive new features.

## 7. Testing Guidance
- **Manual** – Verify that category index/detail pages persist the selected page size while navigating, and that invalid `per_page` values snap back to the closest allowed option. On article pages, switch the comment page size and ensure both the summary text and pagination links update without losing the `#comments` fragment.
- **Automated** – Extend existing Feature tests (e.g., `tests/Feature/AdminCommentsPageTest.php`) to assert the presence of the new dropdowns and that Livewire per-page changes reset pagination state. Browser tests can reuse Playwright helpers to change the selector and confirm the summary text reflects the new range.

Refer back to this document whenever adding new collection views so they inherit the same pagination and component conventions.

