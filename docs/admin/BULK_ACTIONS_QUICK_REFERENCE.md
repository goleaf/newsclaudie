# Bulk Actions - Quick Reference

**Trait**: `App\Livewire\Concerns\ManagesBulkActions`  
**Feature**: admin-livewire-crud

## Quick Start

```php
use App\Livewire\Concerns\ManagesBulkActions;

class PostsIndex extends Component
{
    use ManagesBulkActions;

    public function mount()
    {
        $this->setCurrentPageIds($this->posts->pluck('id'));
    }

    public function bulkPublish()
    {
        Post::whereIn('id', $this->getSelectedIds())
            ->update(['published_at' => now()]);
        $this->clearSelection();
    }
}
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$selected` | `array<int>` | Selected item IDs (URL-persisted) |
| `$selectAll` | `bool` | Select all checkbox state |
| `$currentPageIds` | `array<int>` | IDs on current page |
| `$selectedCount` | `int` | Computed property for count |

## Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `toggleSelection()` | `int $id` | `void` | Toggle item selection |
| `clearSelection()` | - | `void` | Clear all selections |
| `getSelectedIds()` | - | `array<int>` | Get normalized IDs |
| `setCurrentPageIds()` | `iterable<int>` | `void` | Set current page IDs |
| `getSelectedCountProperty()` | - | `int` | Get selection count |

## Blade Template

```blade
{{-- Select all --}}
<input type="checkbox" wire:model.live="selectAll" />

{{-- Individual items --}}
@foreach($items as $item)
    <input 
        type="checkbox" 
        wire:click="toggleSelection({{ $item->id }})"
        @checked(in_array($item->id, $selected))
    />
@endforeach

{{-- Bulk actions --}}
@if($this->selectedCount > 0)
    <div>{{ $this->selectedCount }} selected</div>
    <button wire:click="bulkAction">Action</button>
@endif
```

## Common Patterns

### With Authorization

```php
public function bulkDelete()
{
    $deletable = Post::whereIn('id', $this->getSelectedIds())
        ->get()
        ->filter(fn($p) => auth()->user()->can('delete', $p))
        ->pluck('id');
    
    Post::whereIn('id', $deletable)->delete();
    $this->clearSelection();
}
```

### With Partial Failures

```php
public function bulkApprove()
{
    $ids = $this->getSelectedIds();
    $failures = [];
    
    foreach ($ids as $id) {
        try {
            Comment::findOrFail($id)->approve();
        } catch (\Exception $e) {
            $failures[] = $id;
        }
    }
    
    if (empty($failures)) {
        $this->clearSelection();
    } else {
        $this->selected = $failures; // Keep failed for retry
    }
}
```

### With Bulk Limits

```php
public function bulkPublish()
{
    $ids = $this->getSelectedIds();
    $limit = config('interface.bulk_actions.max_items', 100);
    
    if (count($ids) > $limit) {
        $this->dispatch('notify', "Max {$limit} items", 'error');
        return;
    }
    
    Post::whereIn('id', $ids)->update(['published_at' => now()]);
    $this->clearSelection();
}
```

## URL State

Selections persist in URL:
```
/admin/posts?selected[]=1&selected[]=5&selected[]=12
```

## Testing

```php
// Property tests
php artisan test --filter=BulkSelectionDisplayPropertyTest
php artisan test --filter=BulkOperationSuccessPropertyTest

// Feature tests
Livewire::test(PostsIndex::class)
    ->set('selected', [1, 2, 3])
    ->call('bulkPublish')
    ->assertDispatched('notify');
```

## Key Invariants

- `selectedCount === count(unique(selected))`
- All IDs are integers, unique, filtered
- Double toggle returns to original state
- Clear operation resets to empty state

## Configuration

`config/interface.php`:

```php
'bulk_actions' => [
    'max_items' => 100,
    'timeout' => 30,
],
```

## Related

- [Bulk Actions Architecture](BULK_ACTIONS_ARCHITECTURE.md)
- [Property Tests Index](../../tests/Unit/BULK_ACTIONS_PROPERTY_TESTS_INDEX.md)
- [Admin Configuration](ADMIN_CONFIGURATION.md)
