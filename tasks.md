# BlogNews Project Tasks

## Priority 1: Project Setup
- [ ] Clone Laravel BlogKit repository
- [ ] Install Composer dependencies
- [ ] Install NPM dependencies
- [ ] Configure environment file
- [ ] Run database migrations
- [ ] Generate application key
- [ ] Create storage link
- [ ] Build frontend assets

## Priority 2: Remove Unwanted Features (Per User Rules)
- [ ] Remove all user authentication system
- [ ] Remove user-related migrations, models, controllers
- [ ] Remove comments feature (depends on users)
- [ ] Remove Bootstrap CSS framework
- [ ] Remove all CDN references from blades
- [ ] Ensure only TailwindCSS is used
- [ ] Remove Livewire (per user rules)
- [ ] Remove any export features (CSV, Excel, PDF)
- [ ] Remove any import features except remote JSON
- [ ] Remove reports functionality
- [ ] Consolidate to single layout file

## Priority 3: Categories Feature Implementation
- [ ] Create Category model
- [ ] Create categories migration (id, name, slug, description, timestamps)
- [ ] Create category-post pivot table migration
- [ ] Create CategoryController with CRUD operations
- [ ] Create CategoryRequest for validation
- [ ] Create category routes
- [ ] Create category blade components
- [ ] Add category relationship to Post model
- [ ] Update post creation/editing to include categories
- [ ] Create category listing page
- [ ] Create category detail page (posts by category)
- [ ] Add category filter to posts index

## Priority 4: Multilanguage System
- [ ] Set up JSON-based translation system
- [ ] Create language files (en, additional languages as needed)
- [ ] Implement language switcher component
- [ ] Translate all static strings in blades
- [ ] Translate validation messages
- [ ] Translate error messages
- [ ] Make categories translatable

## Priority 5: Refactor to TailwindCSS
- [ ] Remove all Bootstrap dependencies
- [ ] Rewrite all blade files using TailwindCSS
- [ ] Move all inline CSS to SCSS files in resources
- [ ] Move all inline JS to JS files in resources
- [ ] Create reusable Blade components
- [ ] Ensure all CSS/JS loaded from local NPM packages
- [ ] Run npm run build after changes

## Priority 6: Code Quality & Testing
- [ ] Create FormRequest classes for all controller methods
- [ ] Add validation rules and error messages to all requests
- [ ] Create tests for CategoryController
- [ ] Create tests for Post-Category relationships
- [ ] Run ./vendor/bin/pint for code formatting
- [ ] Run ./vendor/bin/phpstan analyse for static analysis
- [ ] Run php artisan test --parallel
- [ ] Fix all linter errors
- [ ] Fix all test failures

## Priority 7: Route Analysis & Verification
- [ ] List all routes in blade files
- [ ] Verify each route exists in routes/web.php
- [ ] Test each route in browser
- [ ] Document route functionality
- [ ] Fix any broken routes

## Priority 8: Final Cleanup
- [ ] Remove temporary files
- [ ] Remove unused dependencies
- [ ] Update README.md
- [ ] Verify all features work
- [ ] Final code quality check

