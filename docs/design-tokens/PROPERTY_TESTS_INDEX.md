# Design System Property Tests Index

Index of all property-based tests for design system components.

## Overview

Property-based tests validate that components behave correctly across all possible input combinations. These tests use randomized inputs to discover edge cases and ensure robustness.

## Test Files

### Surface Component Tests

**File:** `tests/Unit/SurfaceComponentPropertyTest.php`  
**Component:** `x-ui.surface`  
**Status:** ✅ Complete  
**Tests:** 22 tests, 123 assertions

#### Test Coverage

| Test | Property | Requirements | Status |
|------|----------|--------------|--------|
| All variants produce valid output | Component variant application | 2.1, 2.2, 2.3 | ✅ |
| Variants apply correct background classes | Background class mapping | 2.3 | ✅ |
| Elevations apply correct shadow classes | Shadow class mapping | 2.4 | ✅ |
| Interactive prop adds hover effects | Interactive state transitions | 3.2, 3.5 | ✅ |
| Glass prop adds glassmorphism effect | Backdrop filter application | 2.4 | ✅ |
| Base classes always present | Consistent base styling | 2.1 | ✅ |
| Additional classes merge correctly | Class composition | 6.3 | ✅ |
| Slot content renders correctly | Content rendering | - | ✅ |
| Invalid variant falls back to default | Error handling | - | ✅ |
| Invalid elevation falls back to sm | Error handling | - | ✅ |
| Multiple props combine correctly | Prop interaction | 2.1-2.5 | ✅ |
| Glass overrides variant background | Prop precedence | 2.4 | ✅ |
| All variants include dark mode classes | Dark mode support | 1.6 | ✅ |
| Renders as semantic div | Semantic HTML | 16.1 | ✅ |
| No unnecessary ARIA attributes | Accessibility | 16.2 | ✅ |

#### Running Tests

```bash
# Run all surface tests
php artisan test --filter=SurfaceComponentPropertyTest

# Run with coverage
php artisan test --filter=SurfaceComponentPropertyTest --coverage

# Run specific test
php artisan test --filter="all variants produce valid output"

# Run property tests group
php artisan test --group=property,surface

# Run design system tests
php artisan test --group=design-system
```

#### Test Results

```
✓ all variants produce valid output (0.46s)
✓ variants apply correct background classes (3 datasets, 0.03s)
✓ elevations apply correct shadow classes (5 datasets, 0.05s)
✓ interactive prop adds hover effects (2 datasets, 0.02s)
✓ glass prop adds glassmorphism effect (2 datasets, 0.02s)
✓ base classes are always present (0.02s)
✓ additional classes are merged correctly (0.01s)
✓ slot content renders correctly (0.01s)
✓ invalid variant falls back to default (0.04s)
✓ invalid elevation falls back to sm (0.02s)
✓ multiple props combine correctly (0.02s)
✓ glass effect overrides variant background (0.03s)
✓ all variants include dark mode classes (0.02s)
✓ renders as semantic div without aria attributes (0.01s)

Tests:    22 passed (123 assertions)
Duration: 1.64s
```

---

## Property Testing Patterns

### Pattern 1: Variant Testing

Tests all possible variant values to ensure valid output.

```php
test('all variants produce valid output', function () {
    $variants = ['default', 'subtle', 'ghost'];
    
    foreach ($variants as $variant) {
        $html = Blade::render(
            '<x-ui.surface variant="' . $variant . '">Test</x-ui.surface>'
        );
        
        expect($html)
            ->toBeString()
            ->toContain('Test')
            ->toContain('rounded-2xl');
    }
});
```

### Pattern 2: Dataset Testing

Uses Pest's `with()` to test multiple input combinations.

```php
test('variants apply correct classes', function (string $variant, string $light, string $dark) {
    $html = Blade::render(
        '<x-ui.surface variant="' . $variant . '">Content</x-ui.surface>'
    );
    
    expect($html)
        ->toContain($light)
        ->toContain($dark);
})->with([
    ['default', 'bg-white', 'dark:bg-slate-900'],
    ['subtle', 'bg-slate-50', 'dark:bg-slate-800/50'],
    ['ghost', 'bg-transparent', 'bg-transparent'],
]);
```

### Pattern 3: Boolean Property Testing

Tests both true and false states for boolean props.

```php
test('interactive prop adds hover effects', function (bool $interactive) {
    $html = Blade::render(
        '<x-ui.surface :interactive="' . ($interactive ? 'true' : 'false') . '">Content</x-ui.surface>'
    );
    
    if ($interactive) {
        expect($html)->toContain('hover:shadow-lg');
    } else {
        expect($html)->not->toContain('hover:shadow-lg');
    }
})->with([true, false]);
```

### Pattern 4: Combination Testing

Tests multiple props together to ensure no conflicts.

```php
test('multiple props combine correctly', function () {
    $html = Blade::render(
        '<x-ui.surface variant="subtle" elevation="lg" :interactive="true">Content</x-ui.surface>'
    );
    
    expect($html)
        ->toContain('bg-slate-50')
        ->toContain('shadow-lg')
        ->toContain('hover:shadow-lg');
});
```

### Pattern 5: Fallback Testing

Tests error handling and default values.

```php
test('invalid variant falls back to default', function () {
    $html = Blade::render(
        '<x-ui.surface variant="invalid">Content</x-ui.surface>'
    );
    
    expect($html)
        ->toContain('bg-white')
        ->toContain('dark:bg-slate-900');
});
```

---

## Test Groups

### Available Groups

| Group | Description | Command |
|-------|-------------|---------|
| `property` | All property-based tests | `--group=property` |
| `surface` | Surface component tests | `--group=surface` |
| `design-system` | Design system tests | `--group=design-system` |
| `dark-mode` | Dark mode tests | `--group=dark-mode` |
| `accessibility` | Accessibility tests | `--group=accessibility` |

### Running Groups

```bash
# Run all property tests
php artisan test --group=property

# Run surface component tests
php artisan test --group=surface

# Run design system tests
php artisan test --group=design-system

# Combine groups
php artisan test --group=property,surface,design-system
```

---

## Adding New Property Tests

### Step 1: Create Test File

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/**
 * Component Property Tests
 *
 * @see resources/views/components/ui/component.blade.php
 */

test('property description', function () {
    // Test implementation
})->group('property', 'component-name', 'design-system');
```

### Step 2: Document Test

Add test to this index with:
- Test name
- Property being tested
- Requirements validated
- Status

### Step 3: Update Spec

Mark corresponding task as complete in `../../.kiro/specs/design-system-upgrade/tasks.md`.

---

## Coverage Goals

### Current Coverage

- **Surface Component:** 100% (22/22 tests)
- **Icon Component:** 0% (planned)
- **Spacer Component:** 0% (planned)

### Target Coverage

- All props tested with valid inputs
- All prop combinations tested
- Error handling tested
- Accessibility validated
- Dark mode verified
- Performance validated

---

## Related Documentation

- [Surface Component Tests](../tests/Unit/SurfaceComponentPropertyTest.php)
- [Property Testing Helper](../tests/Helpers/PropertyTesting.php)
- [Surface Component Docs](SURFACE_COMPONENT.md)
- [Design System Spec](../../.kiro/specs/design-system-upgrade/)

---

**Last Updated:** 2025-11-23  
**Test Coverage:** 22 tests, 123 assertions  
**Status:** ✅ All passing
