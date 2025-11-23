# Technology Stack

## Backend

- **Framework**: Laravel 12 (PHP 8.3+)
- **Real-time**: Livewire 3 with Volt (single-file components)
- **UI Components**: Livewire Flux
- **Authentication**: Laravel Sanctum
- **Database**: SQLite (dev), MySQL/PostgreSQL (production)

## Frontend

- **CSS**: TailwindCSS 4
- **JavaScript**: AlpineJS 3, TypeScript
- **Build Tool**: Vite 7
- **Markdown Editor**: EasyMDE

## Testing

- **PHP Testing**: PestPHP 4 with PHPUnit 12
- **Browser Testing**: Playwright
- **Test Suites**: Browser, Feature, Unit
- **Property-Based Testing**: Custom implementation in `tests/Helpers/PropertyTesting.php`

## Code Quality

- **Style**: Laravel Pint (PSR-12 based)
- **Static Analysis**: PHPStan (level max)
- **Refactoring**: Rector
- **Frontend**: Prettier with Tailwind plugin

## Common Commands

### Setup
```bash
composer install          # Install PHP dependencies
npm install              # Install Node dependencies
php artisan key:generate # Generate app key
php artisan migrate      # Run migrations
php artisan storage:link # Link storage
npm run build            # Build assets
php artisan admin:create # Create admin user
```

### Development
```bash
php artisan serve        # Start dev server
npm run dev              # Start Vite dev server
composer dev             # Run all dev services (server, queue, logs, vite)
php artisan pail         # Tail logs
```

### Testing
```bash
php artisan test                    # Run all tests
php artisan test --parallel         # Run tests in parallel
php artisan test --testsuite=Unit   # Run specific suite
npm run playwright:install          # Install Playwright browsers (one-time)
```

### Code Quality
```bash
./vendor/bin/pint                # Auto-format code
./vendor/bin/pint --test         # Check style compliance
./vendor/bin/phpstan analyse     # Run static analysis
rector                           # Run automated refactoring
npm run lint                     # Format frontend code
composer test                    # Run all quality checks
```

### Database
```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh --seed # Fresh DB with seed data
php artisan db:seed              # Seed database
php artisan tinker               # Interactive shell
```

## Configuration Files

- `config/blog.php` - Blog-specific settings (tags, comments, demo mode)
- `config/interface.php` - Admin UI configuration (debounce, bulk limits)
- `config/design-tokens.php` - Design system tokens
- `pint.json` - Code style rules
- `phpstan.neon` - Static analysis configuration
- `phpunit.xml` - Test configuration
- `vite.config.js` - Frontend build configuration
- `tailwind.config.js` - Tailwind customization
