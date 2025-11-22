## Interface Audit Checklist

> Status legend: ☐ pending · ◐ in progress · ☑ done

### 1. Architecture Review
- ☑ Map current Blade + Livewire layout relationships.  
  - Summarized in `docs/INTERFACE_AUDIT_REPORT.md#architecture-snapshot`.
- ☑ List anti-patterns (mixed concerns, inline logic, duplicated queries).  
  - See `docs/INTERFACE_AUDIT_REPORT.md#duplicate--risk-log`.
- ☑ Propose layout/data flow improvements with references.  
  - Recommended consolidations captured under `docs/INTERFACE_AUDIT_REPORT.md#next-actions`.

### 2. Component Optimization
- ☑ Identify duplicated UI fragments (nav, table headers, filters, alerts).  
  - Documented in `docs/INTERFACE_AUDIT_REPORT.md#duplicate--risk-log`.
- ☑ Define target component hierarchy & naming.  
  - `docs/INTERFACE_AUDIT.md#completed-improvements` summarizes the `ui.*` vs `admin.*` split now in production.
- ◐ Specify extraction plan for each repeated fragment.  
  - Pagination/table shells and table headers are done; legacy dashboard widgets/toolbars remain on the backlog (`#next-actions` in the report).

### 3. Duplicate Detection & Elimination
- ☑ Catalog repeated Blade/JS code blocks.  
  - Captured in `docs/INTERFACE_AUDIT_REPORT.md#2-findings--risks`.
- ☑ Decide consolidation approach (Blade components, Livewire traits, JS utils).  
  - Livewire uses `ManagesPerPage`; Blade uses `x-ui.pagination` + shared tables.
- ☑ Track removal of each duplicate.  
  - `PageSize` + validation duplicates resolved; remaining candidates logged in the report backlog.

### 4. Pagination
- ☑ Inventory every list/table/grid (admin + public).  
  - See `docs/INTERFACE_AUDIT_REPORT.md#pagination-coverage`.
- ☑ Choose pagination strategy per surface (offset/cursor).  
  - Offset-only decision recorded in the coverage table.
- ☑ Design unified pagination component (UI + behavior).  
  - `x-ui.pagination` enhancements documented in `docs/INTERFACE_AUDIT_REPORT.md#improvements-in-this-pass` and reused across Blade + Volt.
- ☑ Verify integration with filtering/sorting/search where applicable.  
  - Categories, category detail, comments, and Livewire tables now persist `per_page` parameters alongside filters.

### 5. Refactoring Strategy
- ◐ Break large views/components into single-purpose units.  
  - Split `dashboard.blade.php` (posts/users/comments/analytics) or sunset it in favor of Volt dashboards.
  - Post index Blade now consumes controller-shaped pagination/subtitle data instead of recalculating it locally.
- ☑ Align naming conventions (components, props, CSS utilities).  
  - Pagination props normalized; component naming captured in `docs/INTERFACE_AUDIT.md`.
- ◐ Plan memoization/lazy-loading/state optimizations.  
  - Memoize analytics aggregates + Flux stats in future slices (see `docs/INTERFACE_AUDIT_REPORT.md#next-actions`).
- ☑ Separate business logic (Livewire/Controllers) from presentation.  
  - Controllers own pagination/filter composition; Blade files consume data-only view models.

### 6. Enhanced Solutions
- ☑ Select state management/data fetching patterns (Livewire, Alpine stores).  
  - Admin stays on Livewire Volt; Alpine handles lightweight public interactions (`resources/js/app.ts`).
- ◐ Improve error/loading/empty states & accessibility affordances.  
  - Empty states unified; remaining TODOs include skip links + ARIA summaries (tracked in report backlog).
- ◐ Document performance & DX optimizations.  
  - TypeScript enforcement + helper consolidation done; metrics capture still pending future iterations.

### 7. Documentation & Migration
- ☑ Record final architecture map & component library.  
  - `docs/INTERFACE_AUDIT_REPORT.md` serves as the living reference.
- ☑ Provide migration notes for any template/JS API changes.  
  - See the migration guide and the report's pagination coverage.
- ☑ Outline testing guidance (manual + automated) without modifying tests.  
  - Tracked in `docs/TEST_COVERAGE.md`; future test updates remain on hold.

