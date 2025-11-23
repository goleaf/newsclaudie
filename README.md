<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
<a href="https://github.com/goleaf/newsclaudie"><img src="https://img.shields.io/github/stars/goleaf/newsclaudie?style=social" alt="GitHub stars"></a>
</p>

# Laravel Blog News - Enhanced Blog Starter Kit

## A modern, accessible, and feature-rich Laravel blog application built on Laravel 12, Livewire Volt, Flux UI, TailwindCSS, and AlpineJS.

<img src="https://cdn.jsdelivr.net/gh/caendesilva/laravel-blogkit-static-demo@latest/storage/screenshots/devices/laptop_composite-min.png" />

## Overview

This is an enhanced fork of the Laravel BlogKit with significant improvements to the admin interface, accessibility features, and user experience. The application provides a complete blogging solution with a powerful admin panel, comprehensive testing suite, and extensive documentation.

## ✨ Key Features

### Core Features
* **Modern Admin Interface:** Livewire Volt + Flux UI powered admin panel with real-time updates, optimistic UI, and comprehensive CRUD operations for posts, categories, comments, and users.
* **Tailwind-first UI:** Shared Blade components (`x-ui.*`, `x-navigation.main`) keep the public layout responsive and dark-mode-aware.
* **Category-aware archive:** Filter the public posts index by category with a localized dropdown and matching on-page breadcrumbs.
* **Localization-ready:** JSON translations (English + Spanish) power every nav label, flash message, and validation error. Easily extend by adding locales to `config('app.supported_locales')`.
* **Security-focused CMS:** Posts, categories, users, and comments use dedicated FormRequests and policies so authorization rules live in one place.
* **Smart Markdown editor:** EasyMDE ships by default for rich previews and autosave, configurable via `config/blog.php`.
* **Users & comments:** Email verification gates commenting, with workflow running through classic controllers + Blade forms for easier customization.
* **Semantic, SEO-friendly HTML:** Every post renders OG tags, schema.org metadata, and optional Torchlight highlighting.

### Enhanced Admin Features
* **Optimistic UI Updates:** Instant feedback for user actions with automatic rollback on errors.
* **Advanced Search & Filtering:** Real-time search with debouncing across all admin tables.
* **Sortable Columns:** Multi-column sorting with URL persistence for shareable filtered views.
* **Bulk Actions:** Select and perform actions on multiple items with configurable limits.
* **Inline Editing:** Edit categories and other entities directly in the table view.
* **Accessibility First:** WCAG 2.1 compliant with keyboard navigation, ARIA labels, screen reader support, and focus management.
* **Keyboard Shortcuts:** Power user shortcuts for common admin operations.
* **Loading Indicators:** Smart loading states with configurable delays to prevent UI flicker.
* **Responsive Design:** Fully responsive admin interface that works on all device sizes.

### Developer Experience
* **Comprehensive Documentation:** Extensive documentation in the `docs/` directory covering architecture, components, traits, and best practices.
* **Property-Based Testing:** Advanced testing suite using property-based testing for critical features.
* **Type Safety:** Full TypeScript support for frontend code with comprehensive type definitions.
* **Code Quality:** Integrated with Laravel Pint, PHPStan, and Rector for code quality and consistency.

## 🚀 Quick Start

### Prerequisites
- PHP 8.3 or higher
- Composer
- Node.js and NPM
- SQLite, MySQL, or PostgreSQL

### Installation

```bash
# Clone the repository
git clone https://github.com/goleaf/newsclaudie.git
cd newsclaudie

# Install PHP dependencies
composer install

# Install NPM dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Create storage link
php artisan storage:link

# Build frontend assets
npm run build

# Create admin account
php artisan admin:create

# Start development server
php artisan serve
```

After installation, visit `/admin/dashboard` to access the admin panel.

### Demo Mode Setup

**Important!** Demo mode allows anyone to log in as admin. Only use for demonstration purposes.

1. Set `demoMode` to `true` in `config/blog.php`
2. Run `php artisan migrate --seed`

### Production Setup

1. Clone the repository
2. Run `php artisan migrate` (use `migrate:fresh` if you previously used demo data)
3. Run `php artisan admin:create` to create an admin account with a strong password
4. Follow [Laravel's deployment documentation](https://laravel.com/docs/deployment) for production best practices

### Adding Authors

1. Have the author create a standard account and confirm their email
2. Sign in as administrator and visit `/admin/dashboard`
3. Promote the user by running `php artisan tinker`:
   ```php
   $user = User::where('email', 'author@example.com')->first();
   $user->is_author = true;
   $user->save();
   ```

## 📚 Documentation

Comprehensive documentation is available in the `docs/` directory, grouped by function (for example `docs/admin`, `docs/comments`, `docs/news`, and `docs/design-tokens`). Run `npm run docs:verify` to ensure new Markdown stays inside the docs tree.

### Quick Start Guides
- **[Admin Documentation Index](docs/admin/ADMIN_DOCUMENTATION_INDEX.md)** - Complete documentation overview
- **[Admin Config Quick Reference](docs/admin/ADMIN_CONFIG_QUICK_REFERENCE.md)** - Quick configuration lookup
- **[Volt Component Guide](docs/volt/VOLT_COMPONENT_GUIDE.md)** - Building Livewire Volt components
- **[Livewire Traits Guide](docs/livewire/LIVEWIRE_TRAITS_GUIDE.md)** - Using shared traits
- **[Post Query Scopes Onboarding](docs/query-scopes/POST_QUERY_SCOPES_ONBOARDING.md)** - 10-minute guide to query scopes

### Feature Documentation
- **[Admin Configuration](docs/admin/ADMIN_CONFIGURATION.md)** - Complete configuration reference
- **[Optimistic UI](docs/optimistic-ui/OPTIMISTIC_UI.md)** - Optimistic UI implementation guide
- **[Admin Accessibility](docs/admin/ADMIN_ACCESSIBILITY.md)** - Accessibility features and guidelines
- **[Accessibility Testing Guide](docs/accessibility/ACCESSIBILITY_TESTING_GUIDE.md)** - Testing accessibility features
- **[News Controller Usage](docs/news/NEWS_CONTROLLER_USAGE.md)** - News page filtering and sorting guide
- **[News Controller Refactoring](docs/news/NEWS_CONTROLLER_REFACTORING.md)** - Refactoring notes and service layer
- **[Post Query Scopes](docs/query-scopes/POST_QUERY_SCOPES.md)** - Reusable query scopes for filtering and sorting
- **[Post Query Scopes Quick Reference](docs/query-scopes/POST_QUERY_SCOPES_QUICK_REFERENCE.md)** - Quick lookup guide for query scopes

### API Documentation
- **[News API](docs/api/NEWS_API.md)** - News endpoint API reference

### Architecture
- **[Interface Architecture](docs/interface/INTERFACE_ARCHITECTURE.md)** - System architecture overview
- **[Interface Migration Guide](docs/interface/interface-migration-guide.md)** - Migration from traditional controllers

## 🧪 Testing

The project includes comprehensive test coverage using PestPHP and Playwright:

```bash
# Install dependencies
npm install

# Install Playwright browsers (one-time setup)
npm run playwright:install

# Run all tests
php artisan test

# Run tests in parallel
php artisan test --parallel

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
php artisan test --testsuite=Browser
```

### Test Coverage

- **Browser Tests:** Playwright-powered tests for UI regressions and user flows
- **Feature Tests:** Comprehensive feature testing for controllers, policies, and workflows
- **Unit Tests:** Property-based testing for critical business logic
- **Accessibility Tests:** Automated and manual accessibility testing

### Testing Documentation

- **[Property Testing Guide](tests/PROPERTY_TESTING.md)** - Property-based testing approach and examples
- **[Property Tests Index](tests/Unit/PROPERTY_TESTS_INDEX.md)** - Complete index of all property-based tests
- **[Test Coverage](docs/testing/TEST_COVERAGE.md)** - Complete test coverage inventory

#### Admin CRUD Property Tests
- **[Post Persistence Testing](tests/Unit/POST_PERSISTENCE_PROPERTY_TESTING.md)** - Property tests for post data persistence
- **[Post Persistence Quick Reference](tests/Unit/POST_PERSISTENCE_QUICK_REFERENCE.md)** - Quick reference for post persistence tests

#### Comment Management Property Tests
- **[Comment Status Filter Testing](tests/Unit/COMMENT_STATUS_FILTER_TESTING.md)** - Property tests for comment status filtering
- **[Comment Status Filter Quick Reference](tests/Unit/COMMENT_STATUS_FILTER_QUICK_REFERENCE.md)** - Quick reference for status filter tests

#### News Feature Property Tests
- **[News Filter Options Testing](tests/Unit/NEWS_FILTER_OPTIONS_TESTING.md)** - Property tests for news filters
- **[News Clear Filters Testing](tests/Unit/NEWS_CLEAR_FILTERS_TESTING.md)** - Property tests for clear filters functionality
- **[News View Rendering Testing](tests/Unit/NEWS_VIEW_RENDERING_TESTING.md)** - Property tests for view rendering
- **[News Locale-Aware Navigation Testing](tests/Unit/NEWS_LOCALE_AWARE_NAVIGATION_TESTING.md)** - Property tests for locale-aware navigation

## 🎨 Writing Blog Posts

Blog posts are created using a Markdown editor with live preview.

### Cover Images

Each post has a featured cover image that is dynamically cropped using CSS background properties.

**Best Practices:**
- Recommended size: 960 × 640 pixels (maximum)
- Keep important content within the center 640 × 340 pixels
- Images are cropped to narrower formats on mobile devices

## 🛠️ Technology Stack

### Backend
- **Laravel 12** - Modern PHP framework
- **Livewire 3** - Full-stack framework for Laravel
- **Livewire Volt** - Single-file Livewire components
- **Livewire Flux** - Beautiful admin UI components
- **Laravel Sanctum** - API authentication

### Frontend
- **TailwindCSS 3** - Utility-first CSS framework
- **AlpineJS 3** - Minimal JavaScript framework
- **Vite** - Next-generation frontend tooling
- **TypeScript** - Type-safe JavaScript

### Development Tools
- **PestPHP** - Testing framework
- **Playwright** - Browser automation
- **Laravel Pint** - Code style fixer
- **PHPStan** - Static analysis
- **Rector** - Automated refactoring

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/        # Traditional controllers
│   ├── Requests/            # Form request validation
│   └── Middleware/         # Custom middleware
├── Livewire/
│   └── Concerns/            # Shared Livewire traits
│       ├── ManagesSearch.php
│       ├── ManagesSorting.php
│       ├── ManagesBulkActions.php
│       └── ManagesPerPage.php
├── Models/                  # Eloquent models
└── Support/                 # Helper classes
    └── AdminConfig.php      # Admin configuration helper

resources/
├── views/
│   ├── components/         # Blade components
│   │   ├── admin/          # Admin-specific components
│   │   └── ui/             # Shared UI components
│   └── livewire/
│       └── admin/          # Livewire Volt components
└── js/                     # TypeScript/JavaScript

docs/                       # Comprehensive documentation
tests/                      # Test suites
```

## 🔧 Configuration

### Admin Interface Configuration

Configure the admin interface via `config/interface.php`:

```php
'search_debounce_ms' => 300,        // Search input debounce delay
'form_debounce_ms' => 300,           // Form input debounce delay
'bulk_action_limit' => 100,          // Maximum items for bulk actions
'optimistic_ui_enabled' => true,     // Enable optimistic UI updates
'loading_indicator_delay_ms' => 500,  // Loading spinner delay
```

See [Admin Configuration Guide](docs/admin/ADMIN_CONFIGURATION.md) for complete configuration options.

### Environment Variables

Key environment variables:

```env
APP_NAME="Blog News"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Admin Interface
INTERFACE_SEARCH_DEBOUNCE_MS=300
INTERFACE_BULK_ACTION_LIMIT=100
INTERFACE_OPTIMISTIC_UI_ENABLED=true
```

## 🌍 Localization

The application supports multiple languages through JSON translation files.

### Adding a New Language

1. Copy an existing language file:
   ```bash
   cp lang/en.json lang/fr.json
   ```

2. Translate the content in `lang/fr.json`

3. Register the locale in `config/app.php`:
   ```php
   'supported_locales' => ['en', 'es', 'fr'],
   ```

The locale picker automatically renders buttons for all registered locales.

## 🎯 Navigation & Localization

The primary navigation is defined in `resources/views/components/navigation/main.blade.php` and is fully translation-aware.

### Key Features
1. **Dynamic Route Links** - Extend `$primaryLinks` to add new navigation items
2. **Locale Picker** - POST form with session persistence via `SetLocaleFromSession` middleware
3. **Theme Toggle** - Dark mode support with persistent preferences
4. **Mobile Drawer** - Responsive mobile navigation

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests (`php artisan test`)
5. Run code style checks (`./vendor/bin/pint --test`)
6. Run static analysis (`./vendor/bin/phpstan analyse`)
7. Commit your changes (`git commit -m 'Add amazing feature'`)
8. Push to the branch (`git push origin feature/amazing-feature`)
9. Open a Pull Request

### Code Quality

The project uses several tools to maintain code quality:

```bash
# Auto-format code
./vendor/bin/pint

# Check style compliance
./vendor/bin/pint --test

# Run static analysis
./vendor/bin/phpstan analyse

# Run tests
php artisan test --parallel
```

## 📝 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

This starter kit is also open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

This project is based on [Laravel BlogKit](https://github.com/caendesilva/laravel-blogkit) by [Caen De Silva](https://github.com/caendesilva).

### Open Source Attributions

Built on top of:
- [Laravel Breeze](https://github.com/laravel/breeze) (MIT)
- [TailwindCSS 3](https://tailwindcss.com/) (MIT)
- [AlpineJS 3](https://alpinejs.dev/) (MIT)
- [Laravel 12](https://laravel.com/) (MIT)
- [Livewire](https://livewire.laravel.com/) (MIT)
- [Livewire Flux](https://flux.livewireui.com/) (MIT)
- [Flowbite](https://github.com/themesberg/flowbite) (MIT)

Featured images from [Unsplash](https://unsplash.com/) via [picsum.photos](https://picsum.photos/) ([Image License](https://unsplash.com/license))

## 🔒 Security Vulnerabilities

If you discover a security vulnerability, please send an email to the maintainers. All security vulnerabilities will be promptly addressed.

## 📞 Support

For questions, issues, or contributions:
- Open an issue on [GitHub](https://github.com/goleaf/newsclaudie/issues)
- Check the [documentation](docs/admin/ADMIN_DOCUMENTATION_INDEX.md)
- Review existing issues and discussions

---

**Credit is not required, but it is highly appreciated.** If this project helped you, please leave a star! ⭐
