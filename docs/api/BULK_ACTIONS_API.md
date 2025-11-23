# Bulk Actions API Reference

**Trait**: `App\Livewire\Concerns\ManagesBulkActions`  
**Version**: 1.0  
**Status**: Production-ready

## Overview

API reference for the ManagesBulkActions trait used across admin CRUD tables.

## Public Properties

### `$selected`

```php
#[Url(except: [], as: 'selected')]
public array $selected = [];
```

**Type**: `array<int>`  
**Default**: `[]`  
**URL Persisted**: Yes (as `?selected[]=1&selected[]=2`)  
**Description**: Array of selected item IDs

**Usage**:
```php
// Direct access (not recommended)
$ids = $this->selected;

// Recommended: Use normalized getter
$ids = $this->getSelectedIds();
```

**Notes**:
- IDs may be strings from URL parameters
- May contain duplicates or falsy values
- Always use `getSelectedIds()` for operations

---

### `$selectAll`

```php
public bool $selectAll = false;
```

**Type**: `bool`  
**Default**: `false`  
**URL Persisted**: No  
**Description**: State of the "select all" checkbox

**Usage**:
```blade
<input type="checkbox" wire:model.live="selectAll" />
```

**Lifecycle**:
- Updated via `wire:model.live` binding
- Triggers `updatedSelectAll()` hook
- Automatically synced by `updateSelectAllState()`

---

### `$currentPageIds`

```php
public array $currentPageIds = [];
```

**Type**: `array<int>`  
**Default**: `[]`  
**URL Persisted**: No  
**Description**: IDs of items visible on current page

**Usage**:
```php
public function mount()
{
    $posts = Post::paginate(15);
    $this->setCurrentPageIds($posts->pluck('id'));
}
```

**Notes**:
- Must be set after pagination
- Required for select all functionality
- Should be updated on page changes

---

### `$selectedCount` (Computed)

```php
public function getSelectedCountProperty(): int
```

**Type**: `int` (computed)  
**Access**: `$this->selectedCount`  
**Description**: Count of unique selected items

**Usage**:
```blade
@if($this->selectedCount > 0)
    <span>{{ $this->selectedCount }} selected</span>
@endif
```

**Notes**:
- Automatically normalized (unique, filtered)
- Recalculated on each access
- No caching (fast enough for UI)

## Public Methods

### `toggleSelection()`

```php
public function toggleSelection(int $id): void
```

**Parameters**:
- `$id` (int) - Item ID to toggle

**Returns**: `void`

**Description**: Toggles selection state for a single item

**Usage**:
```blade
<input 
    type="checkbox" 
    wire:click="toggleSelection({{ $item->id }})"
    @checked(in_array($item->id, $selected))
/>
```

**Behavior**:
1. Normalizes current selections
2. Adds ID if not selected
3. Removes ID if already selected
4. Updates selectAll state

**Side Effects**:
- Modifies `$selected` array
- May update `$selectAll` state
- Triggers Livewire re-render

---

### `clearSelection()`

```php
public function clearSelection(): void
```

**Parameters**: None

**Returns**: `void`

**Description**: Clears all selections and resets state

**Usage**:
```php
public function bulkDelete()
{
    Post::whereIn('id', $this->getSelectedIds())->delete();
    $this->clearSelection();
}
```

**Behavior**:
1. Sets `$selected` to empty array
2. Sets `$selectAll` to false
3. Clears URL parameters

**Side Effects**:
- Resets all selection state
- Updates URL (removes `?selected[]` params)
- Triggers Livewire re-render

---

### `getSelectedIds()`

```php
public function getSelectedIds(): array
```

**Parameters**: None

**Returns**: `array<int>` - Normalized array of selected IDs

**Description**: Returns normalized, unique integer IDs

**Usage**:
```php
public function bulkPublish()
{
    $ids = $this->getSelectedIds();
    Post::whereIn('id', $ids)->update(['published_at' => now()]);
}
```

**Normalization**:
1. Casts all values to integers
2. Filters out falsy values (0, null, '')
3. Removes duplicates
4. Re-indexes array (0-based)

**Example**:
```php
$this->selected = ['1', '2', '2', 0, null, '3'];
$this->getSelectedIds(); // [1, 2, 3]
```

---

### `setCurrentPageIds()`

```php
public function setCurrentPageIds(iterable $ids): void
```

**Parameters**:
- `$ids` (iterable<int>) - IDs on current page

**Returns**: `void`

**Description**: Sets current page IDs for select all functionality

**Usage**:
```php
public function mount()
{
    $posts = Post::paginate(15);
    $this->setCurrentPageIds($posts->pluck('id'));
}

public function updatedPage()
{
    $posts = $this->getPosts();
    $this->setCurrentPageIds($posts->pluck('id'));
}
```

**Behavior**:
1. Normalizes provided IDs
2. Stores in `$currentPageIds`
3. Updates `$selectAll` state

**Side Effects**:
- Updates `$currentPageIds` property
- May update `$selectAll` checkbox state
- No Livewire re-render (internal state)

---

### `getSelectedCountProperty()`

```php
public function getSelectedCountProperty(): int
```

**Parameters**: None

**Returns**: `int` - Count of selected items

**Description**: Computed property for selection count

**Usage**:
```php
// In component
$count = $this->selectedCount;

// In Blade
{{ $this->selectedCount }}
```

**Notes**:
- Livewire computed property
- Accessible as `$this->selectedCount`
- Recalculated on each access
- Returns normalized count

## Protected Methods

### `selectCurrentPage()`

```php
protected function selectCurrentPage(): void
```

**Parameters**: None

**Returns**: `void`

**Description**: Selects all items on current page

**Called By**: `updatedSelectAll(true)`

**Behavior**:
1. Merges `$currentPageIds` with `$selected`
2. Normalizes result
3. Sets `$selectAll` to true

---

### `deselectCurrentPage()`

```php
protected function deselectCurrentPage(): void
```

**Parameters**: None

**Returns**: `void`

**Description**: Deselects all items on current page

**Called By**: `updatedSelectAll(false)`

**Behavior**:
1. Removes `$currentPageIds` from `$selected`
2. Preserves selections from other pages
3. Sets `$selectAll` to false

---

### `updateSelectAllState()`

```php
protected function updateSelectAllState(): void
```

**Parameters**: None

**Returns**: `void`

**Description**: Syncs selectAll checkbox with selections

**Called By**: 
- `toggleSelection()`
- `setCurrentPageIds()`

**Behavior**:
1. Checks if all current page IDs are selected
2. Sets `$selectAll` to true if all selected
3. Sets `$selectAll` to false otherwise

---

### `normalizeSelection()`

```php
protected function normalizeSelection(array $ids): array
```

**Parameters**:
- `$ids` (array<int>) - Raw array of IDs

**Returns**: `array<int>` - Normalized array

**Description**: Normalizes ID array to integers, unique, filtered

**Normalization Steps**:
1. Cast to integer: `(int) $id`
2. Filter falsy: `->filter()`
3. Remove duplicates: `->unique()`
4. Re-index: `->values()`

**Example**:
```php
$raw = ['1', '2', '2', 0, null, '3'];
$normalized = $this->normalizeSelection($raw);
// Result: [1, 2, 3]
```

## Lifecycle Hooks

### `updatedSelectAll()`

```php
public function updatedSelectAll(bool $value): void
```

**Trigger**: When `$selectAll` property changes

**Parameters**:
- `$value` (bool) - New value of selectAll

**Behavior**:
- If `true`: Calls `selectCurrentPage()`
- If `false`: Calls `deselectCurrentPage()`

**Usage**: Automatic (Livewire lifecycle)

## URL Parameters

### Query String Format

```
/admin/posts?selected[]=1&selected[]=5&selected[]=12
```

**Parameter**: `selected[]`  
**Type**: Array of integers  
**Persistence**: Automatic via `#[Url]` attribute

**Benefits**:
- Bookmarkable selections
- Browser back/forward support
- Shareable admin links
- Survives page refreshes

**Limitations**:
- URL length limit (~2000 chars)
- Recommend max 50-100 items
- Clear after operations

## Events

No custom events dispatched. Use standard Livewire patterns:

```php
public function bulkPublish()
{
    // Perform operation
    Post::whereIn('id', $this->getSelectedIds())->update([...]);
    
    // Clear selection
    $this->clearSelection();
    
    // Dispatch notification
    $this->dispatch('notify', 'Posts published successfully');
}
```

## Error Handling

### Validation

```php
public function bulkDelete()
{
    $ids = $this->getSelectedIds();
    
    if (empty($ids)) {
        $this->addError('selected', 'No items selected');
        return;
    }
    
    if (count($ids) > 100) {
        $this->addError('selected', 'Maximum 100 items allowed');
        return;
    }
    
    // Process...
}
```

### Authorization

```php
public function bulkDelete()
{
    $ids = $this->getSelectedIds();
    
    $deletable = Post::whereIn('id', $ids)
        ->get()
        ->filter(fn($post) => auth()->user()->can('delete', $post))
        ->pluck('id');
    
    if ($deletable->isEmpty()) {
        $this->addError('selected', 'No posts could be deleted');
        return;
    }
    
    Post::whereIn('id', $deletable)->delete();
}
```

### Partial Failures

```php
public function bulkApprove()
{
    $ids = $this->getSelectedIds();
    $failures = [];
    
    foreach ($ids as $id) {
        try {
            Comment::findOrFail($id)->approve();
        } catch (\Exception $e) {
            $failures[] = ['id' => $id, 'reason' => $e->getMessage()];
        }
    }
    
    if (empty($failures)) {
        $this->clearSelection();
        $this->dispatch('notify', 'All comments approved');
    } else {
        // Keep failed items selected
        $this->selected = collect($failures)->pluck('id')->all();
        $this->dispatch('notify', count($failures) . ' failed', 'warning');
    }
}
```

## Performance Considerations

### Query Optimization

```php
// ✅ Good: Single query
Post::whereIn('id', $this->getSelectedIds())
    ->update(['published_at' => now()]);

// ❌ Bad: N+1 queries
foreach ($this->getSelectedIds() as $id) {
    Post::find($id)->update(['published_at' => now()]);
}
```

### Eager Loading

```php
$posts = Post::with(['user', 'categories'])
    ->whereIn('id', $this->getSelectedIds())
    ->get();
```

### Bulk Limits

```php
$limit = config('interface.bulk_actions.max_items', 100);

if (count($this->getSelectedIds()) > $limit) {
    $this->addError('selected', "Maximum {$limit} items");
    return;
}
```

## Testing

### Unit Tests

```php
use App\Livewire\Concerns\ManagesBulkActions;
use Livewire\Component;

test('toggleSelection adds and removes items', function () {
    $component = new class extends Component {
        use ManagesBulkActions;
        public function render() { return '<div></div>'; }
    };
    
    $component->toggleSelection(1);
    expect($component->getSelectedIds())->toBe([1]);
    
    $component->toggleSelection(1);
    expect($component->getSelectedIds())->toBe([]);
});
```

### Feature Tests

```php
test('bulk publish updates selected posts', function () {
    $user = User::factory()->admin()->create();
    $posts = Post::factory()->count(5)->create(['published_at' => null]);
    
    Livewire::actingAs($user)
        ->test(PostsIndex::class)
        ->set('selected', $posts->pluck('id')->all())
        ->call('bulkPublish')
        ->assertDispatched('notify');
    
    expect(Post::whereNotNull('published_at')->count())->toBe(5);
});
```

### Property Tests

See [Bulk Actions Property Tests Index](../../tests/Unit/BULK_ACTIONS_PROPERTY_TESTS_INDEX.md)

## Related Documentation

- [Bulk Actions Architecture](../admin/BULK_ACTIONS_ARCHITECTURE.md)
- [Bulk Actions Quick Reference](../admin/BULK_ACTIONS_QUICK_REFERENCE.md)
- [Livewire Traits Guide](../livewire/LIVEWIRE_TRAITS_GUIDE.md)
- [Admin Configuration](../admin/ADMIN_CONFIGURATION.md)

## Changelog

See [Admin Changelogs](../admin/changelogs/) for version history.
