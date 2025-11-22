# Interface Migration Guide

## 1. Controllers & Form Requests

1. **Import the helper:** `use App\Support\Pagination\PageSize;`
2. **Define options:** `$options = PageSize::options([12, 24, 36], 12);`
3. **Resolve the per-page value:** `$perPage = PageSize::resolve($request->integer('per_page'), $options, 12);`
4. **Share with Blade:** pass `'pageSizeOptions' => $options` so templates can render the dropdown.
5. **Validate input:** in the matching FormRequest use `Rule::in(PageSize::options(...))` to align validation with the controller.

This guarantees that controllers, requests, and views all agree on the same whitelist of page sizes.

## 2. Blade Views (HTTP)

- Replace raw `{{ $collection->links() }}` calls with `<x-ui.pagination ...>`:

```69:80:resources/views/post/index.blade.php
<x-ui.pagination
    :paginator="$posts"
    per-page-mode="http"
    per-page-field="per_page"
    :per-page-value="$perPage"
    :per-page-options="$perPageOptions"
    :summary="trans('posts.pagination_summary', [
        'from' => $posts->firstItem() ?? 0,
        'to' => $posts->lastItem() ?? 0,
        'total' => $posts->total(),
    ])"
/>
```

- Provide `summary-key` or explicit `summary` text for localized copy.
- When you need to preserve additional query parameters (filters, search terms), pass `:query="request()->except('per_page', 'page')"` to the component.

## 3. Livewire Volt Screens

1. `use App\Livewire\Concerns\ManagesPerPage;` alongside `WithPagination`.
2. Expose a query-string binding for `perPage` if you want the selection persisted: `protected $queryString = ['perPage' => ['except' => 20]];`.
3. Override `availablePerPageOptions()` (and optionally `defaultPerPage()`) to tune the dropdown.
4. Render `<x-admin.table>` (or `<x-ui.pagination>` directly) with `per-page-mode="livewire"` and `per-page-field="perPage"`.

This pattern keeps the dropdown reactive and automatically resets pagination when the user selects a new size.

## 4. TypeScript Globals

- Declare window-level globals inside `resources/js/types/global.d.ts` so every module shares the same augmentation.
- Keep runtime modules (`app.ts`, `bootstrap.ts`) focused on behaviour; no need to redeclare `declare global {}` blocks in each file.
- Run `npm run typecheck` after editing to guarantee the DTS files and TS modules stay in sync.

## 5. Validation & Localization

- Store any new pagination-related validation strings under the `validation.posts.*` namespace in `lang/en.json` / `lang/es.json`.
- Summary copy defaults to `ui.pagination.summary` (see `lang/en/ui.php`). Override via the `summary-key` prop when screens need bespoke wording (e.g. `categories.pagination_summary`).

## 6. Testing & Verification

- **PHP:** `php artisan test --parallel` to validate controller + policy changes.
- **TypeScript:** `npm run typecheck` to ensure the global declarations align with runtime files.
- **Manual:** Verify that changing the per-page selector:
    - Updates the query string (HTTP) or Livewire state (Flux admin).
    - Resets to page 1 and re-renders the table/list without console errors.

Following the above steps keeps the public site, Flux admin, and supporting scripts consistent whenever new resources or screens are added.


