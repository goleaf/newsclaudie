# Technology Stack

## Backend

- **PHP 8.3+** - Modern PHP with strict types
- **Laravel 12** - Latest Laravel framework
- **Livewire 3** - Full-stack reactive components
- **Livewire Volt** - Single-file component syntax
- **Livewire Flux** - Admin UI component library
- **Laravel Sanctum** - API authentication
- **SQLite/MySQL/PostgreSQL** - Database support

## Frontend

- **TailwindCSS 4** - Utility-first CSS framework
- **AlpineJS 3** - Minimal JavaScript framework
- **Vite 7** - Build tool and dev server
- **TypeScript 5** - Type-safe JavaScript
- **EasyMDE** - Markdown editor with live preview

## Development Tools

- **PestPHP 4** - Testing framework (preferred over PHPUnit)
- **Playwright** - Browser automation for E2E tests
- **Laravel Pint** - Code style fixer (Laravel's opinionated PHP-CS-Fixer)
- **PHPStan** - Static analysis tool
- **Rector** - Automated refactoring
- **Prettier** - Frontend code formatting

## Key Libraries

- **graham-campbell/markdown** - Markdown parsing
- **spatie/yaml-front-matter** - YAML frontmatter parsing
- **torchlight** - Syntax highlighting (optional, requires API key)

## Common Commands

### Setup & Installation
```bash
# Full setup (first time)
composer setup

# Manual setup
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
npm run build

# Create admin user
php artisan admin:create
```

### Development
```bash
# Start all dev services (server, queue, logs, vite)
composer dev

# Individual services
php artisan serve              # Development server
php artisan queue:listen       # Queue worker
php artisan pail              # Log viewer
npm run dev                   # Vite dev server
```

### Testing
```bash
# Run all tests
php artisan test

# Run tests in parallel
php artisan test --parallel

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
php artisan test --testsuite=Browser

# Install Playwright browsers (one-time)
npm run playwright:install

# Type coverage
composer test:type-coverage

# Frontend tests
npm run test:optimistic
```

### Code Quality
```bash
# Auto-fix code style
composer lint
./vendor/bin/pint

# Check style without fixing
./vendor/bin/pint --test
composer test:lint

# Static analysis
./vendor/bin/phpstan analyse
composer test:types

# Automated refactoring
./vendor/bin/rector

# Frontend linting
npm run lint                  # Auto-fix
npm run test:lint            # Check only

# TypeScript type checking
npm run typecheck
```

### Database
```bash
# Run migrations
php artisan migrate

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Seed demo data (demo mode only)
php artisan db:seed
```

### Build
```bash
# Production build
npm run build

# Development build with watch
npm run dev
```

## Configuration Files

- `composer.json` - PHP dependencies and scripts
- `package.json` - Node dependencies and scripts
- `phpunit.xml` - Test configuration
- `phpstan.neon` - Static analysis rules
- `pint.json` - Code style rules
- `rector.php` - Refactoring rules
- `vite.config.js` - Build configuration
- `tailwind.config.js` - Tailwind configuration
- `tsconfig.json` - TypeScript configuration

## Environment Variables

Key variables in `.env`:
```env
APP_NAME="Blog News"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Admin Interface
ADMIN_SEARCH_DEBOUNCE_MS=300
ADMIN_BULK_ACTION_LIMIT=100
ADMIN_OPTIMISTIC_UI=true

# Blog Features
BLOGKIT_TAGS_ENABLED=true
BLOGKIT_COMMENTS_ENABLED=true
BLOGKIT_DEMO_MODE=false
```

## Build System

- **Vite** handles all frontend assets
- **Laravel Mix** is NOT used
- Assets are compiled to `public/build/`
- Hot module replacement (HMR) in development
- Automatic CSS purging in production
