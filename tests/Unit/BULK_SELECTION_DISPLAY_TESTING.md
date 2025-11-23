# Bulk Selection Display Property Testing

**Feature**: admin-livewire-crud  
**Property**: 17 - Bulk selection accuracy  
**Validates**: Requirements 8.1, 8.4  
**Test File**: `tests/Unit/BulkSelectionDisplayPropertyTest.php`

## Overview

Property-based tests validating that bulk selection UI accurately reflects the selected state across all admin CRUD tables (posts, categories, comments, users).

## Property Statement

> For any set of selected table rows, the bulk actions toolbar should display and show the correct count of selected items.

## Test Coverage

### 1. Bulk Selection Count Accuracy
- **Iterations**: 100
- **Assertions**: ~800
- **Validates**: Selected count matches actual selection size
- **Edge Cases**: 1-50 random selections, unique IDs, integer normalization

### 2. Empty Selection Displays Zero Count
- **Iterations**: 100
- **Assertions**: ~200
- **Validates**: Initial state and post-clear state both show zero
- **Edge Cases**: Empty array, cleared selection

### 3. Toggle Selection Updates Count
- **Iterations**: 100
- **Assertions**: ~400
- **Validates**: Toggle increments/decrements by exactly 1
- **Edge Cases**: Single item toggle on/off cycle

### 4. Selection Persists Across Multiple Toggles
- **Iterations**: 100
- **Assertions**: ~2,500
- **Validates**: Odd toggles = selected, even toggles = deselected
- **Edge Cases**: 3-10 items with 1-5 toggles each

### 5. Duplicate IDs Are Normalized
- **Iterations**: 100
- **Assertions**: ~400
- **Validates**: Duplicate IDs collapsed to unique set
- **Edge Cases**: 2-5 duplicate entries of same ID

## Total Test Metrics

- **Test Methods**: 5
- **Total Iterations**: 500 (100 per test)
- **Total Assertions**: ~4,300
- **Execution Time**: ~1.0s
- **Trait Under Test**: `App\Livewire\Concerns\ManagesBulkActions`

## Key Invariants Tested

1. **Count Accuracy**: `selectedCount === count(unique(selected))`
2. **Normalization**: All IDs are integers, unique, and filtered
3. **Toggle Idempotence**: Double toggle returns to original state
4. **Clear Completeness**: Clear operation resets to empty state
5. **Persistence**: Selection state survives multiple operations

## Implementation Notes

### Test Pattern
```php
PropertyTesting::run(function ($faker) {
    $component = new class extends Component {
        use ManagesBulkActions;
        public function render() { return '<div></div>'; }
    };
    
    // Generate random test data
    // Perform operations
    // Assert invariants
});
```

### Trait Methods Tested
- `getSelectedCountProperty()` - Computed property for count
- `getSelectedIds()` - Normalized ID array
- `toggleSelection(int $id)` - Toggle single item
- `clearSelection()` - Clear all selections
- `$selected` - Public array property

### Why Anonymous Class?
- Isolates trait behavior from Livewire lifecycle
- No DB/auth dependencies needed
- Fast execution (no HTTP/component boot)
- Matches pattern in other property tests

## Related Tests

- `BulkOperationSuccessPropertyTest.php` - Property 19: Bulk operation completeness
- `BulkPartialFailurePropertyTest.php` - Property 20: Partial failure reporting
- `ManagesBulkActionsTest.php` - Unit tests for trait methods

## Related Documentation

- `BULK_ACTIONS_PROPERTY_TESTS_INDEX.md` - Index of all bulk action property tests
- `docs/admin/ADMIN_CONFIGURATION.md` - Bulk action configuration
- `app/Livewire/Concerns/ManagesBulkActions.php` - Trait implementation

## Running Tests

```bash
# Run this test file only
php artisan test --filter=BulkSelectionDisplayPropertyTest

# Run all bulk action property tests
php artisan test tests/Unit/Bulk*PropertyTest.php

# Run with coverage
php artisan test --filter=BulkSelectionDisplayPropertyTest --coverage

# Run in parallel
php artisan test --filter=BulkSelectionDisplayPropertyTest --parallel
```

## Maintenance Notes

- **Iterations**: Default 100 per test (configurable via `PEST_FAKER_ITERATIONS`)
- **Performance**: Keep execution under 2s total
- **Faker Ranges**: ID ranges 1-10,000 to avoid collisions
- **Cleanup**: No DB cleanup needed (stateless tests)

## Quality Gates

- ✅ Pint formatting
- ✅ PHPStan max level
- ✅ Strict types enforced
- ✅ 100% property coverage for selection display
- ✅ Fast execution (<2s)
- ✅ No external dependencies

## Success Criteria

- [x] All 5 tests pass
- [x] 4,000+ assertions executed
- [x] Execution time < 2s
- [x] No flaky failures across 100 iterations
- [x] Covers all ManagesBulkActions selection methods
