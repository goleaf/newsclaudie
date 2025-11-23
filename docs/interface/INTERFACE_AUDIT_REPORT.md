# Interface Audit Report

_Updated: 2026-02-10_

## 1. Layout & Data Flow Map
- **Public stack** – `PostController` and `CategoryController` hydrate Blade views that lean on `x-ui.page-header`, `x-ui.section`, `x-ui.surface`, and the shared `<x-ui.pagination>` component. Comment threads now share a dedicated pagination helper instead of inlined math.
- **Admin stack** – Livewire Volt routes mount `components.layouts.admin` and reuse `ManagesPerPage` + `<x-admin.table>` so every table gains a consistent toolbar, summary, and pagination control.
- **Support layers** – `App\Support\Pagination\PageSize` owns context defaults; the new `App\Support\Pagination\CommentPageSize` specializes those rules for comment threads and exposes the `comments_per_page` query parameter.

## 2. Findings & Risks
- **Comment pagination drift** – Posting or editing a comment always redirected with the default page size, ignoring the user’s selected `comments_per_page` and sometimes dropping them on the wrong page.
- **Duplicated per-page validation** – Both `PostController@show` and `CommentController@store/update` reimplemented comment page-size sanitisation, risking divergence from `config/interface.php`.
- **Translation namespace drift** – Pagination strings lived under both `pagination.*` and `ui.pagination.*`, leading to inconsistent labels across PHP and JSON locales.
- **Audit doc duplication** – The previous report repeated entire sections, making it hard to trust as the source of truth.

## 3. Remediations Shipped
- Introduced `App\Support\Pagination\CommentPageSize` to centralise defaults, allowed options, and query parameter naming for comment threads.
- Updated `PostController@show` to rely on the helper, drive `<x-comments.list>` with the shared `comments_per_page` field, and push a hidden input into the form so submissions preserve the selected page size.
- Updated `CommentController@store` and `@update` to resolve the requested page size through the helper and propagate both `page` and `comments_per_page` when redirecting back to the post anchor.
- Normalised pagination copy: `ui.pagination.*` is now the primary namespace (with fallbacks to `pagination.*`), standardising “Results per page” labels and summary wording across PHP and JSON locales.
- Rebuilt this report to remove duplication and reflect the current interface state.

## 4. Pagination Inventory
| Surface | Options (default • list) | Query param | Component |
| --- | --- | --- | --- |
| Public posts index | 12 • 12/18/24/36 | `per_page` | `<x-ui.pagination per-page-mode="http">` |
| Categories index | 15 • 12/15/24/30 | `per_page` | `<x-ui.pagination>` |
| Category detail posts | 12 • 9/12/18/24 | `per_page` | `<x-ui.pagination>` |
| Post comments | 10 • 10/25/50/(env default) | `comments_per_page` | `<x-comments.list>` → `<x-ui.pagination>` |
| Admin tables (posts/categories/comments/users) | 20 • 10/20/50/100 | `perPage` (Livewire) | `<x-admin.table>` → `<x-ui.pagination per-page-mode="livewire">` |

## 5. Component & Duplication Notes
- All paginated surfaces now flow through `<x-ui.pagination>`; the comment thread gained the same summary + selector experience as public/admin lists.
- Comment page-size logic now lives in one helper instead of duplicated controller snippets, keeping it aligned with `config/interface.php` and `config/blog.php`.
- Pagination components fall back between `ui.pagination.*` and `pagination.*`, so locales remain consistent even if one namespace lags during translation updates.
- Livewire still centralises per-page handling via `ManagesPerPage`; Blade consumers follow the same conventions through `PageSize`/`CommentPageSize`.

## 6. Recommendations / Backlog
- If the legacy Blade dashboard (`resources/views/dashboard.blade.php`) returns, wrap its tables in `x-ui.data-table` + `x-ui.pagination` and pull per-page options from `PageSize::contextOptions('table')` to avoid bespoke markup.
- Legacy `grid/table` pagination contexts have been removed now that Livewire pages rely on the specific `posts`, `categories`, and `category_posts` options.
- Add a small browser test to assert comment submissions preserve `comments_per_page` and land back on the correct anchor.

## 7. Testing Guidance (manual)
- Change the comments page-size selector on a post, submit a new comment, and confirm the redirect preserves both the selected size and the `#comment-<id>` anchor while showing the correct page.
- Switch per-page values on posts/categories index pages and ensure summaries + dropdowns match the configured options.
- On admin tables, change `perPage` and verify Livewire resets to page 1 while keeping filters intact.
