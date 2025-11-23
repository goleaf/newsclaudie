# Bulk Selection Display - Quick Reference

**Property 17**: Bulk selection accuracy  
**Requirements**: 8.1, 8.4  
**Test File**: `BulkSelectionDisplayPropertyTest.php`

## What It Tests

Selection count accuracy for bulk actions toolbar across admin tables.

## Test Methods

| Test | Validates | Assertions |
|------|-----------|------------|
| `test_bulk_selection_count_accuracy` | Count matches selection size | ~800 |
| `test_empty_selection_displays_zero_count` | Zero count when empty | ~200 |
| `test_toggle_selection_updates_count` | Toggle increments/decrements by 1 | ~400 |
| `test_selection_persists_across_toggles` | Odd toggles = selected | ~2,500 |
| `test_duplicate_ids_are_normalized` | Duplicates collapsed | ~400 |

## Key Invariants

```php
// Count accuracy
selectedCount === count(unique(selected))

// Normalization
all(selected, fn($id) => is_int($id))
selected === array_unique(selected)

// Toggle idempotence
toggle(id); toggle(id); // returns to original

// Clear completeness
clearSelection(); selectedCount === 0
```

## Run Commands

```bash
# Single test
php artisan test --filter=BulkSelectionDisplayPropertyTest

# All bulk tests
php artisan test tests/Unit/Bulk*PropertyTest.php
```

## Trait Under Test

`App\Livewire\Concerns\ManagesBulkActions`

### Methods
- `getSelectedCountProperty()` - Count of selected items
- `getSelectedIds()` - Normalized ID array
- `toggleSelection(int $id)` - Toggle single item
- `clearSelection()` - Clear all selections

### Properties
- `array $selected` - Selected item IDs
- `bool $selectAll` - Select all checkbox state
- `array $currentPageIds` - IDs on current page

## Metrics

- **Tests**: 5
- **Iterations**: 500 total (100 each)
- **Assertions**: ~4,300
- **Time**: ~1.0s
- **Pattern**: Anonymous class with trait

## Related

- `BulkOperationSuccessPropertyTest.php` - Property 19
- `BulkPartialFailurePropertyTest.php` - Property 20
- `BULK_ACTIONS_PROPERTY_TESTS_INDEX.md` - Full index
