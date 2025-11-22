# Project Structure

## Directory Organization

### Application Code (`app/`)

```
app/
├── Console/
│   └── Commands/          # Artisan commands (CreateAdminUser, ResetDemoApp, etc.)
├── Contracts/             # Interfaces (Paginatable, TableDisplayable)
├── Enums/                 # Enumerations (CommentStatus)
├── Exceptions/            # Exception handlers
├── Http/
│   ├── Controllers/       # Traditional controllers
│   │   ├── Auth/         # Authentication controllers
│   │   └── Concerns/     # Controller traits
│   ├── Middleware/        # Custom middleware
│   └── Requests/          # Form request validation classes
├── Jobs/                  # Queue jobs (RunPostExport)
├── Livewire/
│   └── Concerns/          # Shared Livewire traits (IMPORTANT)
├── Models/                # Eloquent models
├── Notifications/         # Email/notification classes
├── Policies/              # Authorization policies
├── Providers/             # Service providers
├── Scopes/                # Global query scopes (PublishedScope)
├── Services/              # Business logic services (NewsFilterService)
├── Support/               # Helper classes
│   ├── AdminConfig.php   # Admin configuration helper
│   ├── Exports/          # Export classes
│   └── Pagination/       # Pagination helpers
└── View/
    └── Components/        # Blade component classes
```

### Views & Frontend (`resources/`)

```
resources/
├── css/
│   └── app.css           # Main stylesheet (Tailwind imports)
├── js/
│   ├── app.ts            # Main JavaScript entry point
│   ├── bootstrap.ts      # Axios setup
│   ├── admin-*.ts        # Admin-specific scripts
│   └── types/            # TypeScript type definitions
├── markdown/             # Example markdown files
└── views/
    ├── auth/             # Authentication views
    ├── categories/       # Category views
    ├── comments/         # Comment views
    ├── components/       # Blade components
    │   ├── admin/        # Admin UI components
    │   ├── categories/   # Category components
    │   ├── comments/     # Comment components
    │   ├── layouts/      # Layout components
    │   ├── navigation/   # Navigation components
    │   ├── news/         # News components
    │   └── ui/           # Shared UI components (IMPORTANT)
    ├── flux/             # Flux component overrides
    ├── layouts/          # Base layouts
    ├── livewire/         # Livewire Volt components
    │   └── admin/        # Admin Volt pages
    ├── news/             # News views
    ├── post/             # Post views
    └── vendor/           # Vendor view overrides
```

### Configuration (`config/`)

Key configuration files:
- `blog.php` - Blog-specific settings (tags, comments, demo mode)
- `interface.php` - Admin interface configuration (pagination, debounce, bulk actions)
- `app.php` - Application settings (locales, timezone)
- `database.php` - Database connections
- `markdown.php` - Markdown parser settings

### Database (`database/`)

```
database/
├── factories/            # Model factories for testing
├── migrations/           # Database migrations
└── seeders/              # Database seeders
```

### Tests (`tests/`)

```
tests/
├── Browser/              # Playwright E2E tests
├── Feature/              # Feature tests (controllers, policies)
│   ├── Admin/           # Admin-specific feature tests
│   └── Auth/            # Authentication tests
├── Unit/                 # Unit tests (models, services, traits)
├── Helpers/              # Test helper classes
└── js/                   # Frontend tests
```

### Documentation (`docs/`)

Comprehensive documentation organized by topic:
- `ADMIN_*.md` - Admin interface documentation
- `ACCESSIBILITY_*.md` - Accessibility guides
- `INTERFACE_*.md` - Interface architecture
- `NEWS_*.md` - News feature documentation
- `OPTIMISTIC_UI*.md` - Optimistic UI implementation
- `api/` - API documentation

## Key Architectural Patterns

### Livewire Volt Components

Admin pages use single-file Volt components in `resources/views/livewire/admin/`:
- `posts.index.blade.php` - Posts listing
- `categories.index.blade.php` - Categories listing
- `comments.index.blade.php` - Comments listing
- `users.index.blade.php` - Users listing

Each Volt component:
1. Declares `layout('components.layouts.admin')`
2. Uses traits from `app/Livewire/Concerns/`
3. Implements table display with search, sort, and bulk actions

### Shared Livewire Traits

Located in `app/Livewire/Concerns/`, these provide reusable functionality:
- `ManagesSearch.php` - Search functionality with debouncing
- `ManagesSorting.php` - Multi-column sorting with URL persistence
- `ManagesBulkActions.php` - Bulk action handling
- `ManagesPerPage.php` - Per-page selection
- `InteractsWithBulkSelection.php` - Bulk selection state

### Blade Component System

**UI Components** (`resources/views/components/ui/`):
- `badge.blade.php` - Status badges
- `card.blade.php` - Card containers
- `data-table.blade.php` - HTTP-based tables
- `empty-state.blade.php` - Empty state messages
- `page-header.blade.php` - Page headers
- `pagination.blade.php` - Pagination controls
- `section.blade.php` - Content sections
- `surface.blade.php` - Base surface container

**Admin Components** (`resources/views/components/admin/`):
- `table.blade.php` - Livewire-based admin tables
- `bulk-actions.blade.php` - Bulk action toolbar
- `search-input.blade.php` - Search input with debouncing

### Form Request Validation

All form validation uses dedicated FormRequest classes in `app/Http/Requests/`:
- `Store*Request.php` - Creation validation
- `Update*Request.php` - Update validation
- `*IndexRequest.php` - Index/filtering validation

### Authorization

Policies in `app/Policies/` handle all authorization:
- `PostPolicy.php` - Post permissions
- `CommentPolicy.php` - Comment permissions
- `UserPolicy.php` - User management permissions

### Service Layer

Business logic extracted to services in `app/Services/`:
- `NewsFilterService.php` - News filtering and sorting logic

## Naming Conventions

### PHP
- **Classes:** PascalCase (`PostController`, `ManagesSearch`)
- **Methods:** camelCase (`index()`, `bulkDelete()`)
- **Properties:** camelCase (`$perPage`, `$searchTerm`)
- **Constants:** SCREAMING_SNAKE_CASE (`BULK_ACTION_LIMIT`)
- **Strict types:** Always declare `declare(strict_types=1);`

### Blade
- **Components:** kebab-case (`<x-ui.card>`, `<x-admin.table>`)
- **Slots:** camelCase (`{{ $headerActions }}`)
- **Props:** kebab-case (`per-page-mode="livewire"`)

### JavaScript/TypeScript
- **Files:** kebab-case (`admin-post-actions.ts`)
- **Functions:** camelCase (`initThemeToggle()`)
- **Classes:** PascalCase (`AdminConfig`)
- **Constants:** SCREAMING_SNAKE_CASE (`DEBOUNCE_MS`)

### Database
- **Tables:** snake_case plural (`posts`, `categories`, `category_post`)
- **Columns:** snake_case (`created_at`, `is_published`)
- **Foreign keys:** `{model}_id` (`user_id`, `post_id`)

## File Locations

### When to create new files:

**Controllers:** `app/Http/Controllers/` for traditional request/response
**Livewire Components:** `resources/views/livewire/` for Volt single-file components
**Blade Components:** `resources/views/components/` with class in `app/View/Components/`
**Traits:** `app/Livewire/Concerns/` for Livewire, `app/Http/Controllers/Concerns/` for controllers
**Services:** `app/Services/` for complex business logic
**Helpers:** `app/Support/` for utility classes
**Tests:** Mirror the `app/` structure in `tests/Unit/` or `tests/Feature/`

## Import Paths

### PHP
```php
use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use App\Livewire\Concerns\ManagesSearch;
use App\Support\AdminConfig;
```

### TypeScript
```typescript
import Alpine from 'alpinejs';
import axios from 'axios';
// Relative imports for local modules
import { initThemeToggle } from './utils';
```

## Configuration Access

Use helper classes for type-safe config access:
```php
// Preferred
AdminConfig::searchDebounceMs()
AdminConfig::bulkActionLimit()

// Avoid direct config() calls in components
config('interface.admin.search_debounce_ms') // Less type-safe
```
