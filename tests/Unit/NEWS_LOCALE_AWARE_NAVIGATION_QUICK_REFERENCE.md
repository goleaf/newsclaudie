# News Locale-Aware Navigation - Quick Reference

**Test File**: `tests/Unit/NewsLocaleAwareNavigationPropertyTest.php`  
**Full Documentation**: [NEWS_LOCALE_AWARE_NAVIGATION_TESTING.md](NEWS_LOCALE_AWARE_NAVIGATION_TESTING.md)

## Quick Commands

```bash
# Run all locale navigation tests
php artisan test tests/Unit/NewsLocaleAwareNavigationPropertyTest.php

# Run specific test
php artisan test --filter=test_news_link_displays_in_current_locale
php artisan test --filter=test_locale_switching_updates_navigation_label
php artisan test --filter=test_unsupported_locale_uses_fallback
php artisan test --filter=test_multiple_renders_produce_consistent_labels

# Run with verbose output
php artisan test tests/Unit/NewsLocaleAwareNavigationPropertyTest.php --verbose

# Run by group
php artisan test --group=locale
php artisan test --group=navigation
php artisan test --group=property-testing
```

## Test Summary

| Test | Iterations | Assertions | Purpose |
|------|-----------|-----------|---------|
| Basic locale display | 6 | 24 | Verify translation in current locale |
| Locale switching | 10 | 70 | Verify label updates on locale change |
| Unsupported locale fallback | 1 | 4 | Verify graceful fallback to English |
| Idempotence | 10 renders | 14 | Verify consistent rendering |
| **TOTAL** | **27** | **115** | **Complete coverage** |

## Supported Locales

| Locale | Code | Translation |
|--------|------|-------------|
| English | `en` | News |
| Spanish | `es` | Noticias |

## Properties Tested

### Property 21: Locale-Aware Navigation

**Rule**: News link displays in current locale

**Validates**: Requirement 9.4

**Coverage**:
- ✅ Translation key resolution
- ✅ Correct translation per locale
- ✅ Label changes on locale switch
- ✅ Fallback for unsupported locales
- ✅ Idempotent rendering

## Quick Troubleshooting

### Translation not found
```bash
# Check translation files
cat lang/en.json | jq '.["nav.news"]'
cat lang/es.json | jq '.["nav.news"]'

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Locale not switching
```bash
# Test in tinker
php artisan tinker
>>> App::setLocale('es');
>>> __('nav.news');  # Should return "Noticias"
```

### Navigation not updating
```bash
# Clear view cache
php artisan view:clear

# Check component
cat resources/views/components/navigation/main.blade.php | grep "nav.news"
```

## Adding New Locale

1. **Create translation file**: `lang/fr.json`
```json
{
    "nav.news": "Actualités"
}
```

2. **Update test**:
```php
private array $supportedLocales = ['en', 'es', 'fr'];
private array $expectedTranslations = [
    'en' => 'News',
    'es' => 'Noticias',
    'fr' => 'Actualités',
];
```

3. **Run tests**:
```bash
php artisan test tests/Unit/NewsLocaleAwareNavigationPropertyTest.php
```

## Common Assertions

```php
// Translation resolves
$this->assertNotEquals('nav.news', __('nav.news'));

// Correct translation
$this->assertEquals('News', __('nav.news'));

// In rendered HTML
$this->assertStringContainsString('News', $html);

// Not in wrong locale
$this->assertStringNotContainsString('News', $spanishHtml);

// Idempotence
$this->assertEquals($render1, $render2);
```

## Test Data

### Translation Files

**`lang/en.json`**:
```json
{
    "nav.news": "News"
}
```

**`lang/es.json`**:
```json
{
    "nav.news": "Noticias"
}
```

### Component Usage

```blade
{{-- Navigation component --}}
<a href="{{ route('news.index') }}">
    {{ __('nav.news') }}
</a>
```

## Performance

- **Total duration**: ~0.27s
- **Fastest test**: 0.02s (fallback)
- **Slowest test**: 0.12s (locale switching)
- **Average**: 0.07s per test

## Related Files

- **Test**: `tests/Unit/NewsLocaleAwareNavigationPropertyTest.php`
- **Component**: `resources/views/components/navigation/main.blade.php`
- **Translations**: `lang/en.json`, `lang/es.json`
- **Controller**: `app/Http/Controllers/LocaleController.php`
- **Middleware**: `app/Http/Middleware/SetLocaleFromSession.php`

## Documentation

- [Full Testing Guide](NEWS_LOCALE_AWARE_NAVIGATION_TESTING.md)
- [Property Testing Guide](../PROPERTY_TESTING.md)
- [Test Coverage](../../docs/testing/TEST_COVERAGE.md)
- [Requirements](../../.kiro/specs/news-page/requirements.md)
