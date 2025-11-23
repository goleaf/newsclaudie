# News Locale-Aware Navigation Property Testing

**Last Updated**: 2025-11-23  
**Test File**: `tests/Unit/NewsLocaleAwareNavigationPropertyTest.php`  
**Component Under Test**: `resources/views/components/navigation/main.blade.php`

## Overview

This document describes the property-based testing approach for the News Locale-Aware Navigation feature. These tests verify that the News navigation link displays correctly in all supported locales and handles locale switching gracefully.

## What is Property-Based Testing?

Property-based testing verifies universal properties that should hold true across all valid inputs. Instead of testing specific examples, we test general rules by running many iterations with randomized data.

### Example Property

**Property**: "The News navigation link must display in the current application locale"

This property must be true whether:
- The locale is English or Spanish
- The user switches locales multiple times
- The locale is unsupported (should fall back gracefully)
- The navigation is rendered multiple times

## Properties Tested

### Property 21: Locale-Aware Navigation

**Universal Rule**: For any supported locale, the "News" navigation link should display the label in the current locale's language.

**Test Coverage**:
- ✅ Translation key resolves correctly for each locale
- ✅ Rendered navigation contains translated label
- ✅ Label changes when locale changes
- ✅ Translation is not empty or fallback key
- ✅ Multiple renders produce consistent labels (idempotence)
- ✅ Unsupported locales fall back to English gracefully

**Requirements Validated**: 9.4

## Test Strategy

### Supported Locales

The tests verify behavior for:
- **English (en)**: "News"
- **Spanish (es)**: "Noticias"

### Iteration Counts

- **Basic locale test**: 6 iterations (2 locales × 3 repetitions)
- **Locale switching test**: 10 iterations
- **Fallback test**: 1 iteration (deterministic)
- **Idempotence test**: 2 locales × 5 renders each = 10 renders

### Randomization Strategy

Each iteration randomly selects a locale:

```php
// Random locale selection
$locale = $this->supportedLocales[array_rand($this->supportedLocales)];
App::setLocale($locale);
```

### Expected Translations

```php
private array $expectedTranslations = [
    'en' => 'News',
    'es' => 'Noticias',
];
```

## Test Methods

### 1. test_news_link_displays_in_current_locale()

**Purpose**: Verify that the News link displays in the current locale

**Iterations**: 6 (2 locales × 3 repetitions)

**Properties Verified**:
1. Translation key resolves correctly
2. Translation matches expected value for locale
3. Translation is not empty
4. Rendered navigation contains translated label

**Assertions per iteration**: 4  
**Total assertions**: 24

### 2. test_locale_switching_updates_navigation_label()

**Purpose**: Verify that switching locales updates the navigation label

**Iterations**: 10

**Properties Verified**:
1. English label is correct
2. English label appears in rendered navigation
3. Spanish label is correct
4. Spanish label appears in rendered navigation
5. Labels are different between locales
6. English label doesn't appear in Spanish navigation
7. Spanish label doesn't appear in English navigation

**Assertions per iteration**: 7  
**Total assertions**: 70

### 3. test_unsupported_locale_uses_fallback()

**Purpose**: Verify graceful fallback for unsupported locales

**Iterations**: 1 (deterministic)

**Properties Verified**:
1. Translation key resolves (not returned as-is)
2. Falls back to English translation
3. Navigation renders without errors
4. Navigation is not empty

**Total assertions**: 4

### 4. test_multiple_renders_produce_consistent_labels()

**Purpose**: Verify idempotence (same input = same output)

**Iterations**: 2 locales × 5 renders each

**Properties Verified**:
1. All translation calls return the same value
2. All renders contain the same translated label
3. First and last render are identical

**Assertions**: 14 (2 locales × 7 assertions each)

## Running the Tests

### Run all locale navigation tests
```bash
php artisan test tests/Unit/NewsLocaleAwareNavigationPropertyTest.php
```

### Run specific test
```bash
php artisan test --filter=test_news_link_displays_in_current_locale
```

### Run with verbose output
```bash
php artisan test tests/Unit/NewsLocaleAwareNavigationPropertyTest.php --verbose
```

### Run by group
```bash
# All property tests
php artisan test --group=property-testing

# All navigation tests
php artisan test --group=navigation

# All locale tests
php artisan test --group=locale
```

## Test Results

### Expected Output

```
PASS  Tests\Unit\NewsLocaleAwareNavigationPropertyTest
✓ news link displays in current locale           0.08s
✓ locale switching updates navigation label      0.12s
✓ unsupported locale uses fallback              0.02s
✓ multiple renders produce consistent labels     0.05s

Tests:    4 passed (115 assertions)
Duration: 0.27s
```

### Assertion Breakdown

- **Basic locale test**: 4 assertions × 6 iterations = 24 assertions
- **Locale switching**: 7 assertions × 10 iterations = 70 assertions
- **Fallback test**: 4 assertions × 1 iteration = 4 assertions
- **Idempotence test**: 7 assertions × 2 locales = 14 assertions
- **Verification assertions**: 3 assertions

**Total**: 115 assertions across all tests

## Understanding Test Failures

### Failure: Translation key not resolved

```
Failed asserting that two strings are equal.
--- Expected
+++ Actual
@@ @@
-'News'
+'nav.news'

Translation key 'nav.news' should resolve for locale 'en'
```

**Diagnosis**: Translation file is missing or key is not defined.

**Check**:
1. `lang/en.json` contains `"nav.news": "News"`
2. `lang/es.json` contains `"nav.news": "Noticias"`
3. Translation files are properly formatted JSON
4. Cache is cleared: `php artisan config:clear`

### Failure: Wrong translation value

```
Failed asserting that two strings are equal.
--- Expected
+++ Actual
@@ @@
-'Noticias'
+'News'

News link should display 'Noticias' for locale 'es'
```

**Diagnosis**: Translation is not using the correct locale.

**Check**:
1. `App::setLocale()` is being called correctly
2. Translation files have correct values
3. No middleware overriding locale
4. Session locale is not interfering

### Failure: Label appears in wrong locale

```
Failed asserting that 'rendered HTML...' does not contain "News".

Navigation should not contain 'News' when locale is 'es'
```

**Diagnosis**: Navigation is not respecting the current locale.

**Check**:
1. Navigation component uses `__('nav.news')` not hardcoded text
2. Locale is set before rendering
3. No caching of rendered navigation
4. Blade cache is cleared: `php artisan view:clear`

### Failure: Inconsistent renders

```
Failed asserting that two strings are equal.

First and last render should be identical for locale 'en'
```

**Diagnosis**: Rendering is not idempotent (has side effects).

**Check**:
1. No random elements in navigation
2. No time-dependent content
3. No global state being modified
4. Component is stateless

## Integration with Application

### Translation Files

**English** (`lang/en.json`):
```json
{
    "nav.news": "News"
}
```

**Spanish** (`lang/es.json`):
```json
{
    "nav.news": "Noticias"
}
```

### Navigation Component Usage

```blade
{{-- resources/views/components/navigation/main.blade.php --}}
<nav>
    <a href="{{ route('news.index') }}" 
       @class(['active' => request()->routeIs('news.*')])>
        {{ __('nav.news') }}
    </a>
</nav>
```

### Locale Switching

```php
// In LocaleController
public function update(SetLocaleRequest $request)
{
    $locale = $request->validated('locale');
    session(['locale' => $locale]);
    App::setLocale($locale);
    
    return redirect()->back();
}
```

### Middleware

```php
// In SetLocaleFromSession middleware
public function handle($request, Closure $next)
{
    if (session()->has('locale')) {
        App::setLocale(session('locale'));
    }
    
    return $next($request);
}
```

## Related Documentation

- [Quick Reference](NEWS_LOCALE_AWARE_NAVIGATION_QUICK_REFERENCE.md) - Fast-access commands and tips
- [Property Testing Guide](../PROPERTY_TESTING.md) - General property testing approach
- [News Feature Requirements](../../.kiro/specs/news-page/requirements.md) - Feature requirements
- [Test Coverage](../../docs/TEST_COVERAGE.md) - Overall test coverage

## Maintenance Notes

### When to Update These Tests

1. **When adding new locales**: Add to `$supportedLocales` and `$expectedTranslations`
2. **When changing translation keys**: Update key references in tests
3. **When navigation structure changes**: Update component rendering assertions
4. **When locale logic changes**: Update middleware or controller tests

### Adding New Locales

To add a new locale (e.g., French):

1. **Add translation file**: `lang/fr.json`
```json
{
    "nav.news": "Actualités"
}
```

2. **Update test arrays**:
```php
private array $supportedLocales = ['en', 'es', 'fr'];

private array $expectedTranslations = [
    'en' => 'News',
    'es' => 'Noticias',
    'fr' => 'Actualités',
];
```

3. **Run tests** to verify:
```bash
php artisan test tests/Unit/NewsLocaleAwareNavigationPropertyTest.php
```

### Performance Considerations

These tests are fast (< 0.3s total) because they:
- Don't use database operations
- Use simple view rendering
- Have low iteration counts
- Test deterministic behavior

### Common Issues

**Issue**: Tests fail after adding new locale
**Solution**: Ensure translation file exists and is valid JSON

**Issue**: Fallback test fails
**Solution**: Verify `config/app.php` has `fallback_locale => 'en'`

**Issue**: Locale not persisting
**Solution**: Check middleware order and session configuration

## Troubleshooting

### Translation not found

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Verify translation files
cat lang/en.json | jq .
cat lang/es.json | jq .
```

### Locale not switching

```bash
# Check session configuration
php artisan tinker
>>> session()->put('locale', 'es');
>>> App::getLocale();
```

### Navigation not updating

```bash
# Clear view cache
php artisan view:clear

# Check component syntax
php artisan view:cache
```

## Contributing

When contributing to these tests:

1. ✅ Follow the existing documentation pattern
2. ✅ Add clear property descriptions
3. ✅ Include requirement references
4. ✅ Use appropriate test groups
5. ✅ Update translation files for new locales
6. ✅ Update this documentation
7. ✅ Add examples to quick reference

## Questions?

For questions about these tests, see:
- [Quick Reference](NEWS_LOCALE_AWARE_NAVIGATION_QUICK_REFERENCE.md)
- [Property Testing Guide](../PROPERTY_TESTING.md)
- [Test Coverage Documentation](../../docs/TEST_COVERAGE.md)
- Project maintainers
