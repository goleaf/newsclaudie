## Interface Audit Checklist

> Status legend: ☐ pending · ◐ in progress · ☑ done

### 1. Architecture Review
- ☑ Map current Blade + Livewire layout relationships.  
  - Summarized in `docs/INTERFACE_AUDIT_REPORT.md#1-layout--data-flow-map`.
- ☑ List anti-patterns (mixed concerns, inline logic, duplicated queries).  
  - See `docs/INTERFACE_AUDIT_REPORT.md#2-anti-patterns--risks`.
- ☑ Propose layout/data flow improvements with references.  
  - Recommended consolidations captured under `docs/INTERFACE_AUDIT_REPORT.md#5-enhanced-solutions--recommendations`.

### 2. Component Optimization
- ☑ Identify duplicated UI fragments (nav, table headers, filters, alerts).  
  - Documented in `docs/INTERFACE_AUDIT_REPORT.md#2-anti-patterns--risks`.
- ☑ Define target component hierarchy & naming.  
  - `docs/INTERFACE_AUDIT.md#completed-improvements` summarizes the `ui.*` vs `admin.*` split now in production.
- ◐ Specify extraction plan for each repeated fragment.  
  - Pagination/table shells are done; legacy dashboard widgets/toolbars remain on the backlog (§5 of the report).

### 3. Duplicate Detection & Elimination
- ☑ Catalog repeated Blade/JS code blocks.  
  - Captured in `docs/INTERFACE_AUDIT_REPORT.md#2-anti-patterns--risks`.
- ☑ Decide consolidation approach (Blade components, Livewire traits, JS utils).  
  - Livewire uses `ManagesPerPage`; Blade uses `x-ui.pagination` + shared tables.
- ☑ Track removal of each duplicate.  
  - `PageSize` + validation duplicates resolved; remaining candidates logged in the report backlog.

### 4. Pagination
- ☑ Inventory every list/table/grid (admin + public).  
  - See `docs/INTERFACE_AUDIT_REPORT.md#4-pagination-pipeline`.
- ☑ Choose pagination strategy per surface (offset/cursor).  
  - Offset-only decision recorded in the report table.
- ☑ Design unified pagination component (UI + behavior).  
  - `x-ui.pagination` enhancements documented in §3 of the report and reused across Blade + Volt.
- ☑ Verify integration with filtering/sorting/search where applicable.  
  - Categories, category detail, comments, and Livewire tables now persist `per_page` parameters alongside filters.

### 5. Refactoring Strategy
- ◐ Break large views/components into single-purpose units.  
  - Split `dashboard.blade.php` (posts/users/comments/analytics) or sunset it in favor of Volt dashboards.
- ☑ Align naming conventions (components, props, CSS utilities).  
  - Pagination props normalized; component naming captured in `docs/INTERFACE_AUDIT.md`.
- ◐ Plan memoization/lazy-loading/state optimizations.  
  - Memoize analytics aggregates + Flux stats in future slices (see report §5).
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
  - See §6 of the report.
- ☑ Outline testing guidance (manual + automated) without modifying tests.  
  - Covered in §7 of the report; future test updates tracked in `docs/TEST_COVERAGE.md`.

