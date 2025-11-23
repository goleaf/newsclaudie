# Blade Route Audit (2025‑11‑22)

This document inventories every `route()` usage across `resources/views` and cross-checks each reference against the definitions in `routes/web.php` and `routes/auth.php`.

| Route | Defined in | Blade references | Notes |
| --- | --- | --- | --- |
| `home` | `routes/web.php` (`/`) | `livewire/posts/index.blade.php`, `components/navigation/main.blade.php` | `Route::has('home')` guard avoids failures if renamed. |
| `locale.update` | `routes/web.php` | `components/navigation/main.blade.php` | POST form toggles locale selector. Requires CSRF tokens (already present via Blade component). |
| `posts.index` | `Volt::route('posts', 'posts.index')` | `livewire/posts/index`, `post/show`, `components/post-card`, `components/post-tags` | Query params (`author`, `filterByTag`) flow as URL parameters and are validated inside the Volt component. |
| `posts.show` | `Route::resource('posts', ...)` | `post/show`, `components/post-card` | Used for canonical/meta tags and navigation. |
| `posts.create` | `Route::resource` | `post/create` | Author/editor CTA for creating posts. |
| `posts.store` | `Route::resource` | `post/create` | Form submission. |
| `posts.edit` | `Route::resource` | `post/show` | Edit links for authors/admins. |
| `posts.update` | `Route::resource` | `post/edit` | Form submission. |
| `posts.destroy` | `Route::resource` | — | No current Blade references (handled via controller logic). |
| `posts.publish` | `routes/web.php` custom | `post/show`, `livewire/admin/posts/index` | Additional action for toggling state. |
| `categories.index` | `Volt::route('categories', 'categories.index')` | `categories/create`, `categories/edit`, `categories/show` | Livewire page (search + per-page + admin delete gate). |
| `categories.create` | `Route::resource` | `categories/index` | CTA. |
| `categories.store` | `Route::resource` | `categories/create` | Form submission. |
| `categories.show` | `Volt::route('categories/{category}', 'categories.show')` | `categories/index` | Volt page with `livewire/category-posts` paginator. |
| `categories.edit` | `Route::resource` | `categories/index` | Edit links. |
| `categories.update` | `Route::resource` | `categories/edit` | Form submission. |
| `categories.destroy` | `Route::resource` | `categories/index` | Delete forms include method spoofing + CSRF. |
| `posts.comments.store` | `routes/web.php` custom | `post/show` | Handles the new Blade-based comment form (`POST /posts/{post}/comments`). |
| `comments.edit` | `Route::resource('comments')` (edit/update/destroy) | `post/show`, `comments/edit` | Edit links jump to dedicated page.
| `comments.update` | Same as above | `comments/edit` | Form request (`UpdateCommentRequest`) validates max length + policy. |
| `comments.destroy` | Same as above | `post/show` | Delete forms use `DELETE` spoof and CSRF. |
| `admin.dashboard` | `routes/web.php` (Volt) | `components/navigation/main`, `components/layouts/admin`, `livewire/admin/dashboard` | Flux Livewire admin shell (auth + `can:access-admin`). |
| `admin.posts.index` | `routes/web.php` (Volt) | `livewire/admin/posts/index` | Livewire Volt page for managing posts. |
| `admin.categories.index` | `routes/web.php` (Volt) | `livewire/admin/categories/index` | Livewire Volt page for managing categories. |
| `admin.comments.index` | `routes/web.php` (Volt) | `livewire/admin/comments/index` | Livewire Volt page for moderating comments. |
| `admin.users.index` | `routes/web.php` (Volt) | `livewire/admin/users/index` | Manage newsroom roles/ban status. |
| `verification.notice` | `routes/auth.php` | `post/show` | Displayed when unverified commenters. |
| `verification.send` | `routes/auth.php` | `auth/verify-email` | Resend email form. |
| `login` | `routes/auth.php` | `post/show`, `auth/login`, `auth/register` | Standard Breeze routes. |
| `register` | `routes/auth.php` | `post/show`, `auth/login`, `auth/register` | Wrapped in config guard (registration toggle) at route level; Blade should respect same config. |
| `logout` | `routes/auth.php` | `auth/verify-email` | POST form with CSRF. |
| `password.request` | `routes/auth.php` | `auth/login` | Link to password reset request. |
| `password.email` | `routes/auth.php` | `auth/forgot-password` | Form submission. |
| `password.reset` | `routes/auth.php` | *(implicit via `$request->route('token')` usage)* | Token retrieved via request. |
| `password.update` | `routes/auth.php` | `auth/reset-password` | Form submission. |
| `password.confirm` | `routes/auth.php` | `auth/confirm-password` | Confirmation form. |
| `locale.update` | `routes/web.php` | `navigation/main` | Already verified. |

### Observations
- No Blade template references an undefined route. All names resolve to either resource routes or entries inside `routes/auth.php`.
- Several Blade files still contain inline styles (`style="..."`) or Bootstrap-esque utility classes, which violates the Tailwind-only requirement. These will be addressed in subsequent steps.
- The JSON scan produced an entry labeled `"token"` stemming from `{{ $request->route('token') }}`; this is not a route name and requires no action.

### Next Steps
1. Convert this audit into actionable work by prioritizing Tailwind rewrites per Blade category (layouts, posts, categories, etc.).
2. Verify the runtime behavior in-browser for a subset of key routes (`home`, `posts.index`, `categories.index`) once frontend refactors are ready.
3. Document any controller/FormRequest gaps discovered while tracing the routes (e.g., ensure `LocaleController@update` uses a dedicated request with translated validation errors).
