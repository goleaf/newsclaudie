# Admin Configuration Quick Reference

Quick reference for common admin configuration tasks.

## Quick Access

```php
use App\Support\AdminConfig;

// Get timing values
AdminConfig::searchDebounceMs();        // 300
AdminConfig::formDebounceMs();          // 300
AdminConfig::textareaDebounceMs();      // 400
AdminConfig::loadingIndicatorDelayMs(); // 500

// Get limits
AdminConfig::bulkActionLimit();              // 100
AdminConfig::bulkActionWarningThreshold();   // 50

// Check settings
AdminConfig::optimisticUiEnabled();          // true/false
AdminConfig::persistFiltersInUrl();          // true/false
AdminConfig::requireDeleteConfirmation();    // true/false

// Validation helpers
AdminConfig::exceedsBulkActionLimit(150);    // true
AdminConfig::shouldWarnBulkAction(60);       // true

// Blade directive helpers
AdminConfig::searchDebounceDirective();      // "wire:model.live.debounce.300ms"
AdminConfig::formDebounceDirective();        // "wire:model.live.debounce.300ms"
AdminConfig::textareaDebounceDirective();    // "wire:model.live.debounce.400ms"
```

## Common Patterns

### Search Input

```blade
<input
    type="search"
    wire:model.live.debounce.{{ \App\Support\AdminConfig::searchDebounceMs() }}ms="search"
    placeholder="Search..."
/>
```

### Form Input with Validation

```blade
<input
    type="text"
    wire:model.live.debounce.{{ \App\Support\AdminConfig::formDebounceMs() }}ms="name"
    placeholder="Name"
/>
```

### Textarea

```blade
<textarea
    wire:model.live.debounce.{{ \App\Support\AdminConfig::textareaDebounceMs() }}ms="description"
    rows="4"
></textarea>
```

### Bulk Action Validation

```php
public function bulkDelete(): void
{
    if (AdminConfig::exceedsBulkActionLimit($this->selectedCount)) {
        $this->addError('selected', 'Selection exceeds maximum of ' . AdminConfig::bulkActionLimit());
        return;
    }

    if (AdminConfig::shouldWarnBulkAction($this->selectedCount)) {
        $this->dispatch('confirm-bulk-action', count: $this->selectedCount);
        return;
    }

    // Proceed with bulk delete...
}
```

### Loading Indicator

```blade
<div wire:loading.delay.{{ \App\Support\AdminConfig::loadingIndicatorDelayMs() }}ms>
    <x-admin.loading-spinner />
</div>
```

## Environment Overrides

```env
# .env file
ADMIN_SEARCH_DEBOUNCE_MS=300
ADMIN_FORM_DEBOUNCE_MS=300
ADMIN_TEXTAREA_DEBOUNCE_MS=400
ADMIN_BULK_ACTION_LIMIT=100
ADMIN_BULK_WARNING_THRESHOLD=50
ADMIN_OPTIMISTIC_UI=true
ADMIN_LOADING_DELAY_MS=500
ADMIN_PERSIST_FILTERS=true
ADMIN_REQUIRE_DELETE_CONFIRM=true
```

## Preset Configurations

### Development
```env
ADMIN_SEARCH_DEBOUNCE_MS=100
ADMIN_FORM_DEBOUNCE_MS=100
ADMIN_BULK_ACTION_LIMIT=10
ADMIN_REQUIRE_DELETE_CONFIRM=false
```

### Production
```env
ADMIN_SEARCH_DEBOUNCE_MS=300
ADMIN_FORM_DEBOUNCE_MS=300
ADMIN_BULK_ACTION_LIMIT=100
ADMIN_REQUIRE_DELETE_CONFIRM=true
```

### Testing
```env
ADMIN_SEARCH_DEBOUNCE_MS=0
ADMIN_FORM_DEBOUNCE_MS=0
ADMIN_BULK_ACTION_LIMIT=1000
ADMIN_REQUIRE_DELETE_CONFIRM=false
ADMIN_OPTIMISTIC_UI=false
```

## Performance Tuning

| Network Speed | Search Debounce | Form Debounce | Textarea Debounce |
|--------------|-----------------|---------------|-------------------|
| Fast (< 50ms) | 200ms | 200ms | 300ms |
| Standard (50-150ms) | 300ms | 300ms | 400ms |
| Slow (> 150ms) | 500ms | 400ms | 500ms |

| Dataset Size | Bulk Limit | Warning Threshold |
|-------------|------------|-------------------|
| Small (< 10K) | 200 | 100 |
| Medium (10K-100K) | 100 | 50 |
| Large (> 100K) | 50 | 25 |

## Related Files

- Configuration: `config/interface.php`
- Helper Class: `app/Support/AdminConfig.php`
- Full Documentation: `docs/admin/ADMIN_CONFIGURATION.md`

