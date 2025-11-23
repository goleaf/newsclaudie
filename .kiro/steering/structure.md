# Project Structure

## Directory Organization

### Application Code (`app/`)

```
app/
├── Console/Commands/      # Artisan commands (CreateAdminUser, ResetDemoApp, etc.)
├── Contracts/             # Interfaces (Paginatable, TableDisplayable, etc.)
├── Enums/                 # Enums (CommentStatus)
├── Http/
│   ├── Controllers/       # Traditional controllers (Category, Comment, Post, News, etc.)
│   ├── Middleware/        # Custom middleware (SecurityHeaders, SetLocaleFromSession, etc.)
│   └── Requests/          # Form request validation (Store*, Update*, *IndexRequest)
├── Jobs/                  # Queue jobs (RunPostExport)
├── Livewire/Concerns/     # Shared Livewire traits (ManagesSearch, ManagesSorting, etc.)
├── Models/                # Eloquent models (Post, Category, Comment, User, etc.)
├── Policies/              # Authorization policies (PostPolicy, CommentPolicy, etc.)
├── Scopes/                # Global query scopes (PublishedScope)
├── Services/              # Business logic services (NewsFilterService)
├── Support/               # Helper classes (AdminConfig, DesignTokens, PageSize)
└── View/Components/       # Blade components (AppLayout, PostCard, etc.)
```

### Views (`resources/views/`)

```
resources/views/
├── auth/                  # Authentication views
├── categories/            # Category CRUD views
├── comments/              # Comment views
├── components/            # Blade components
│   ├── admin/            # Admin-specific components
│   ├── navigation/       # Navigation components
│   └── ui/               # Shared UI components
├── livewire/             # Livewire Volt components
│   ├── admin/            # Admin panel components (dashboard, posts, categories, users, comments)
│   ├── categories/       # Category management
│   ├── post/             # Post-related components
│   └── posts/            # Posts index
├── news/                 # News/archive views
└── post/                 # Post display views
```

### Frontend Assets (`resources/`)

```
resources/
├── css/
│   └── app.css           # Main Tailwind entry point
└── js/
    ├── admin-optimistic-ui.ts    # Optimistic UI implementation
    ├── admin-post-actions.ts     # Admin post actions
    ├── app.ts                    # Main JS entry
    ├── bootstrap.ts              # Bootstrap (Axios, Alpine)
    └── types/                    # TypeScript definitions
```

### Configuration (`config/`)

Key config files:
- `blog.php` - Blog features (tags, comments, demo mode, EasyMDE)
- `interface.php` - Admin UI settings (debounce, bulk limits, optimistic UI)
- `design-tokens.php` - Design system tokens
- `security.php` - Security headers and CSP
- `analytics.php` - Analytics configuration

### Database (`database/`)

```
database/
├── factories/            # Model factories for testing
├── migrations/           # Database migrations (chronological)
└── seeders/             # Database seeders (AdminUser, Category, Post, News, Demo)
```

### Tests (`tests/`)

```
tests/
├── Browser/             # Playwright browser tests
├── Feature/             # Feature tests (controllers, policies, workflows)
│   └── Admin/          # Admin-specific feature tests
├── Unit/                # Unit tests (models, services, property tests)
├── Helpers/             # Test helpers (PropertyTesting)
└── js/                  # JavaScript/TypeScript tests
```

### Documentation (`docs/`)

Comprehensive documentation organized by topic:
- Admin features and configuration
- Accessibility guidelines
- Architecture and design patterns
- API references
- Testing guides
- Quick reference guides

## Architectural Patterns

### Controllers
- Traditional controllers for public routes
- Livewire Volt components for admin panel
- Form Requests for validation
- Policies for authorization

### Models
- Eloquent ORM with relationships
- Query scopes for reusable filters (`scopeFilterByCategories`, `scopePublished`, etc.)
- Accessors/Mutators for computed attributes
- Global scopes for default behavior (PublishedScope)

### Livewire Components
- Single-file Volt components in `resources/views/livewire/`
- Shared traits in `app/Livewire/Concerns/` (ManagesSearch, ManagesSorting, ManagesBulkActions, etc.)
- Optimistic UI for instant feedback
- Real-time updates without page refresh

### Frontend
- Tailwind utility-first CSS
- Alpine.js for lightweight interactivity
- TypeScript for type safety
- Vite for fast builds

## Naming Conventions

### PHP
- **Classes**: PascalCase, final by default
- **Methods**: camelCase
- **Properties**: camelCase
- **Constants**: UPPER_SNAKE_CASE
- **Strict types**: Always declare `declare(strict_types=1);`

### Database
- **Tables**: plural snake_case (`posts`, `categories`, `category_post`)
- **Columns**: snake_case (`published_at`, `user_id`)
- **Foreign keys**: `{model}_id` pattern

### Views
- **Blade files**: kebab-case (`index.blade.php`, `admin-dashboard.blade.php`)
- **Components**: kebab-case with dot notation (`x-ui.button`, `x-navigation.main`)

### Routes
- **Public**: RESTful resource routes (`/posts`, `/categories/{category}`)
- **Admin**: Prefixed with `/admin` (`/admin/dashboard`, `/admin/posts`)

## Key Files

- `app/Support/helpers.php` - Global helper functions
- `app/Support/AdminConfig.php` - Admin configuration helper
- `app/Support/DesignTokens.php` - Design token management
- `routes/web.php` - Web routes
- `routes/volt.php` - Livewire Volt routes (admin panel)
- `lang/en.json` - JSON translations
- `lang/en/*.php` - PHP translation files
