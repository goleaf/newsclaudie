# Interface Architecture

## 1. Layout Shells

| Surface | Entry point | Responsibilities |
| --- | --- | --- |
| Public site | `resources/views/layouts/app.blade.php` | Injects navigation (`x-navigation.main`), meta tags, Alpine-driven theme toggles, and the shared footer. Feature views slot in via `<x-app-layout>`. |
| Flux admin | `resources/views/components/layouts/admin.blade.php` | Wraps Livewire Volt pages with the Flux sidebar, breadcrumb nav, and status toasts. Each Volt view calls `layout('components.layouts.admin')`. |

Both shells keep the view-model thin: controllers focus on fetching data, while the views compose UI components.

## 2. UI Component Library

- **Primitives:** `x-ui.surface`, `x-ui.card`, `x-ui.section`, and `x-ui.page-header` define spacing, typography, and responsive padding. They eliminate custom section wrappers scattered across views such as `categories/index`, `post/index`, and `welcome`.
- **Feedback states:** `x-ui.badge`, `x-ui.empty-state`, `x-auth-session-status`, and Flux callouts provide a consistent language for status messages and ephemeral alerts.
- **Data tables:** `x-ui.data-table` (HTTP) and `x-admin.table` (Livewire/Flux) wrap the `<table>` markup, slotting in table headers/rows and delegating pagination to `x-ui.pagination`.

## 3. Pagination Pipeline

```1:55:app/Support/Pagination/PageSize.php
public static function resolve(?int $requested, array $allowed, int $default): int
{
    $options = self::options($allowed, $default);

    if ($requested === null) {
        return $default;
    }

    return in_array($requested, $options, true) ? $requested : $default;
}
```

- **Controller/request layer:** `PostController`, `CategoryController`, and `PostIndexRequest` call `PageSize::options + PageSize::resolve` to sanitize user input and to expose `$*PageSizeOptions` variables to Blade.
- **Blade layer:** `<x-ui.pagination>` is the single widget responsible for summary copy, Laravel paginator links, and per-page selectors. It accepts `per-page-mode="http"` for standard Blade views and `per-page-mode="livewire"` for Volt screens, piping the chosen value back to the correct query string or Livewire property.
- **Livewire layer:** Components use `App\Livewire\Concerns\ManagesPerPage` to keep `$perPage` in sync with the dropdown, reset pagination after changes, and expose `$this->perPageOptions` to the template.

## 4. Admin Table Composition

`x-admin.table` orchestrates:

1. An optional toolbar slot (bulk actions, filters).
2. Flux card wrapping for consistent padding/dark-mode support.
3. `<table>` markup with `head` and default body classes.
4. A pagination footer that delegates to `<x-ui.pagination>` with the correct summary text and per-page bindings.

Each Volt admin page (`admin.posts.index`, `admin.categories.index`, `admin.comments.index`, `admin.users.index`) simply provides head/row slots and passes its paginator instance.

## 5. TypeScript Runtime

```1:22:resources/js/app.ts
import './bootstrap';
import '../css/app.css';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
```

- `resources/js/bootstrap.ts` sets up Axios (with typed `window.axios`) and is bundled by Vite.
- Utility initializers (`initThemeToggle`, `initMobileNav`, `initSlugInputs`) run on `DOMContentLoaded`. Each helper now uses DOM generics so TypeScript enforces correct element types.
- Global typings live in `resources/js/types/global.d.ts`, keeping module files minimal while ensuring `npm run typecheck` catches regressions.

## 6. Documentation & Testing Hooks

- `docs/INTERFACE_AUDIT_REPORT.md` captures architectural decisions, anti-patterns, and follow-up tasks.
- `docs/INTERFACE_MIGRATION.md` (see companion file) explains how to adopt the pagination stack or TypeScript globals in future features.
- `npm run typecheck` gates TypeScript regressions, while `php artisan test --parallel` (see `PROJECT_SUMMARY.md`) covers backend flows without modifying test fixtures.


