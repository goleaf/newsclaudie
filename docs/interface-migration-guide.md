# Interface Migration Guide

This companion to `INTERFACE_MIGRATION.md` gives the quick-start steps for adopting the shared pagination and table components.

## Essentials

1. **Use context presets** – Pull defaults/options from `config/interface.php` via `PageSize::contextOptions($context)` and `PageSize::contextDefault($context)`. Contexts ship for `posts`, `categories`, `category_posts`, `admin`, and `comments`.
2. **Standard query param** – `PageSize::queryParam()` returns `per_page`; comment threads use `CommentPageSize::queryParam()` (`comments_per_page`).
3. **Render the shared component** – `<x-ui.pagination>` (or `<x-admin.table>` / `<x-ui.table>`) handles summaries, per-page selectors, aria labels, and hides controls when not needed.

## Blade (HTTP) Example

```php
$param = PageSize::queryParam();
$options = PageSize::contextOptions('posts');
$perPage = PageSize::resolve($request->integer($param), $options, PageSize::contextDefault('posts'));
```

```blade
<x-ui.pagination
    :paginator="$posts"
    per-page-mode="http"
    per-page-field="{{ $param }}"
    :per-page-options="$options"
    :per-page-value="$perPage"
    summary-key="posts.pagination_summary"
/>
```

## Livewire (Volt) Example

```php
use App\Support\Pagination\PageSize;
use App\Livewire\Concerns\ManagesPerPage;

protected $queryString = ['perPage' => ['except' => PageSize::contextDefault('admin')]];
```

```blade
<x-admin.table
    :pagination="$resources"
    per-page-mode="livewire"
    per-page-field="perPage"
    :per-page-options="$this->perPageOptions"
/>
```

## Comments

```php
$commentPerPage = CommentPageSize::resolveFromRequest($request);
$commentOptions = CommentPageSize::options();
```

```blade
<x-comments.list
    :comments="$comments"
    :per-page-options="$commentOptions"
    :per-page-value="$commentPerPage"
    per-page-field="{{ CommentPageSize::queryParam() }}"
    per-page-anchor="comments"
/>
```

Include a hidden `comments_per_page` input on comment forms so readers keep their selection after posting.
When redirecting after create/update, call `CommentPageSize::locatePage($comment, $post, $commentPerPage)` and pass the `comments_per_page` query param only when it differs from the default to keep anchors in sync.

## Translations

- Prefer `ui.pagination.*` (`summary`, `per_page`, `aria_label`). Collection-specific overrides remain optional (e.g. `posts.pagination_summary`).
- Provide `ui.pagination.per_page_label` (or `ui.pagination.per_page`) for dropdown labels; legacy `pagination.*` keys remain only for compatibility.
- Validation messages belong under `validation.*.per_page_options` to stay aligned with FormRequests.
