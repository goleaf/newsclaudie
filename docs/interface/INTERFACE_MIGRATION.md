# Interface Migration Guide

> Canonical, actively maintained copy: `docs/interface/interface-migration-guide.md`. Keep this file as a thin pointer to avoid guidance drifting between the two.

## 1. Controllers & Form Requests

1. Import the helper: `use App\Support\Pagination\PageSize;`
2. Pick a context from `config/interface.php` (e.g. `posts`, `categories`, `category_posts`, `admin`, `comments`).
3. Resolve options + value:

```php
$perPageParam = PageSize::queryParam();
$options = PageSize::contextOptions('posts');
$perPage = PageSize::resolve(
    $request->integer($perPageParam),
    $options,
    PageSize::contextDefault('posts'),
);
```

4. In FormRequests, validate with the same options: `Rule::in(PageSize::contextOptions('posts'))`.
5. Pass `$options`, `$perPage`, and `$perPageParam` to Blade so selectors stay in sync.

## 2. Blade Views (HTTP)

Replace raw `links()` calls with the shared component:

```blade
<x-ui.pagination
    :paginator="$posts"
    per-page-mode="http"
    per-page-field="{{ $perPageParam }}"
    :per-page-value="$perPage"
    :per-page-options="$options"
    summary-key="posts.pagination_summary"
/>
```

`per-page-field` already defaults to `PageSize::queryParam()`; override it only when the route intentionally diverges. When preserving additional query params (filters/search), pass `:query="request()->except([$perPageParam, 'page'])"`.

## 3. Livewire Volt Screens

1. `use App\Livewire\Concerns\ManagesPerPage;` with `WithPagination`.
2. Persist the selector in the query string:  
   `protected $queryString = ['perPage' => ['except' => PageSize::contextDefault('admin')]];`
3. Render `<x-admin.table :pagination="$dataset" />`; it defaults to Livewire mode (`perPage` binding) with config-backed options.
4. Avoid manual summaries—`x-admin.table` + `x-ui.pagination` handle `ui.pagination.summary` automatically.

## 4. Comment Threads

- Use `App\Support\Pagination\CommentPageSize` for the canonical `comments_per_page` param:
  ```php
  $perPage = CommentPageSize::resolveFromRequest($request);
  $options = CommentPageSize::options();
  ```
- In `post.show`:
  ```blade
  <x-comments.list
      :comments="$comments"
      :per-page-options="$options"
      :per-page-value="$perPage"
      per-page-field="{{ CommentPageSize::queryParam() }}"
      per-page-anchor="comments"
  />
  ```
- Keep the reader’s choice after submitting a comment with a hidden input for `comments_per_page`.

## 5. Translations & Validation

- Use `ui.pagination.*` (`summary`, `per_page`, `aria_label`) for all locales. Collection-specific summaries (e.g. `posts.pagination_summary`) remain optional overrides.
- Validation messages for unsupported sizes belong under `validation.posts.per_page_options` (and similar) so FormRequests and views stay aligned.

## 6. Manual Verification

- Change page-size selectors across posts, categories, and comments; ensure the query string updates and pagination resets to page 1.
- Flip per-page values in Volt admin tables and confirm the dropdown reflects the persisted `?perPage=` query parameter after refresh.
- Try invalid `per_page` values and confirm the UI snaps back to the nearest allowed option without losing filters.


