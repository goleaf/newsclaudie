# Bulk Actions Architecture

**Feature**: admin-livewire-crud  
**Component**: ManagesBulkActions trait  
**Status**: Production-ready

## Overview

The bulk actions system provides consistent selection and batch operation capabilities across all admin CRUD tables (posts, categories, comments, users). Built with Livewire 3 and URL-persisted state for bookmarkable selections.

## Architecture

### Component Structure

```
app/Livewire/Concerns/
└── ManagesBulkActions.php          # Shared trait for selection logic

resources/views/livewire/admin/
├── posts/index.blade.php           # Bulk publish/unpublish
├── categories/index.blade.php      # Bulk delete
├── comments/index.blade.php        # Bulk approve/reject/delete
└── users/index.blade.php           # Bulk role/status updates

tests/Unit/
├── BulkSelectionDisplayPropertyTest.php    # Property 17: Selection accuracy
├── BulkOperationSuccessPropertyTest.php    # Property 19: Operation completeness
└── BulkPartialFailurePropertyTest.php      # Property 20: Failure reporting
```

### Data Flow

```
User Action → Livewire Component → ManagesBulkActions Trait → Database
     ↓              ↓                       ↓                      ↓
  Checkbox    toggleSelection()    normalizeSelection()    Bulk Query
     ↓              ↓                       ↓                      ↓
  Select All  setCurrentPageIds()  getSelectedIds()       Update/Delete
     ↓              ↓                       ↓                      ↓
  URL State   clearSelection()     selectedCount          Feedback
```

## Core Components

### ManagesBulkActions Trait

**Purpose**: Provides reusable selection tracking and state management for Livewire components.

**Public Properties**:
- `array $selected` - Selected item IDs (URL-persisted via `#[Url]` attribute)
- `bool $selectAll` - Select all checkbox state
- `array $currentPageIds` - IDs visible on current page

**Public Methods**:
- `toggleSelection(int $id)` - Toggle individual item selection
- `clearSelection()` - Reset all selections
- `getSelectedCountProperty()` - Computed property for count (accessible as `$this->selectedCount`)
- `setCurrentPageIds(iterable $ids)` - Update current page IDs for select all
- `getSelectedIds()` - Get normalized array of selected IDs

**Protected Methods**:
- `selectCurrentPage()` - Select all items on current page
- `deselectCurrentPage()` - Deselect all items on current page
- `updateSelectAllState()` - Sync selectAll checkbox with selections
- `normalizeSelection(array $ids)` - Ensure IDs are integers, unique, filtered

### Selection Normalization

All IDs are normalized through a consistent pipeline:

1. **Cast to integer**: Handles string IDs from URL parameters
2. **Filter falsy values**: Removes null, 0, empty strings
3. **Remove duplicates**: Ensures unique set
4. **Re-index**: Returns zero-indexed array

```php
protected function normalizeSelection(array $ids): array
{
    return collect($ids)
        ->map(fn($id) => (int) $id)
        ->filter()
        ->unique()
        ->values()
        ->all();
}
```

## Usage Patterns

### Basic Implementation

```php
use App\Livewire\Concerns\ManagesBulkActions;
use Livewire\Component;

class PostsIndex extends Component
{
    use ManagesBulkActions;

    public function mount()
    {
        // Set current page IDs for select all functionality
        $posts = Post::paginate(15);
        $this->setCurrentPageIds($posts->pluck('id'));
    }

    public function bulkPublish()
    {
        $ids = $this->getSelectedIds();
        
        Post::whereIn('id', $ids)
            ->whereNull('published_at')
            ->update(['published_at' => now()]);
        
        $this->clearSelection();
        $this->dispatch('notify', 'Posts published successfully');
    }
}
```

### Blade Template

```blade
{{-- Select all checkbox --}}
<input 
    type="checkbox" 
    wire:model.live="selectAll"
    aria-label="Select all items on this page"
/>

{{-- Individual item checkboxes --}}
@foreach($posts as $post)
    <input 
        type="checkbox" 
        wire:click="toggleSelection({{ $post->id }})"
        @checked(in_array($post->id, $selected))
        aria-label="Select {{ $post->title }}"
    />
@endforeach

{{-- Bulk actions toolbar --}}
@if($this->selectedCount > 0)
    <div class="bulk-actions">
        <span>{{ $this->selectedCount }} selected</span>
        <button wire:click="bulkPublish">Publish</button>
        <button wire:click="clearSelection">Clear</button>
    </div>
@endif
```

### With Authorization

```php
public function bulkDelete()
{
    $ids = $this->getSelectedIds();
    
    // Filter to only items user can delete
    $deletable = Post::whereIn('id', $ids)
        ->get()
        ->filter(fn($post) => auth()->user()->can('delete', $post))
        ->pluck('id');
    
    if ($deletable->isEmpty()) {
        $this->dispatch('notify', 'No posts could be deleted', 'error');
        return;
    }
    
    Post::whereIn('id', $deletable)->delete();
    
    $failed = count($ids) - $deletable->count();
    if ($failed > 0) {
        $this->dispatch('notify', "{$deletable->count()} deleted, {$failed} failed", 'warning');
    } else {
        $this->dispatch('notify', 'Posts deleted successfully');
    }
    
    $this->clearSelection();
}
```

### Partial Failure Handling

```php
public function bulkApprove()
{
    $ids = $this->getSelectedIds();
    $attempted = count($ids);
    $failures = [];
    
    foreach ($ids as $id) {
        try {
            $comment = Comment::findOrFail($id);
            
            if (!auth()->user()->can('approve', $comment)) {
                $failures[] = ['id' => $id, 'reason' => 'Unauthorized'];
                continue;
            }
            
            $comment->update(['status' => CommentStatus::Approved]);
        } catch (\Exception $e) {
            $failures[] = ['id' => $id, 'reason' => $e->getMessage()];
        }
    }
    
    $updated = $attempted - count($failures);
    
    if (empty($failures)) {
        $this->clearSelection();
        $this->dispatch('notify', "{$updated} comments approved");
    } else {
        // Keep failed items selected for retry
        $this->selected = collect($failures)->pluck('id')->all();
        $this->dispatch('notify', "{$updated} approved, " . count($failures) . " failed", 'warning');
    }
}
```

## URL State Persistence

Selections are persisted in the URL query string via Livewire's `#[Url]` attribute:

```
/admin/posts?selected[]=1&selected[]=5&selected[]=12
```

**Benefits**:
- Bookmarkable selections
- Browser back/forward support
- Shareable admin links
- Survives page refreshes

**Considerations**:
- URL length limits (~2000 chars)
- Recommend bulk limits (50-100 items)
- Clear selections after operations

## Performance Considerations

### Query Optimization

```php
// ✅ Good: Single query with whereIn
Post::whereIn('id', $this->getSelectedIds())
    ->update(['published_at' => now()]);

// ❌ Bad: N+1 queries
foreach ($this->getSelectedIds() as $id) {
    Post::find($id)->update(['published_at' => now()]);
}
```

### Bulk Limits

Configure in `config/interface.php`:

```php
'bulk_actions' => [
    'max_items' => 100,
    'timeout' => 30,
],
```

Enforce in components:

```php
public function bulkPublish()
{
    $ids = $this->getSelectedIds();
    $limit = config('interface.bulk_actions.max_items', 100);
    
    if (count($ids) > $limit) {
        $this->dispatch('notify', "Maximum {$limit} items allowed", 'error');
        return;
    }
    
    // Process bulk action...
}
```

### Eager Loading

```php
// Load relationships for bulk operations
$posts = Post::with(['user', 'categories'])
    ->whereIn('id', $this->getSelectedIds())
    ->get();
```

## Testing Strategy

### Property-Based Tests

**Property 17: Bulk selection accuracy** (`BulkSelectionDisplayPropertyTest.php`)
- Validates selection count accuracy
- Tests toggle idempotence
- Verifies normalization
- 500 iterations, ~4,300 assertions

**Property 19: Bulk operation completeness** (`BulkOperationSuccessPropertyTest.php`)
- Validates all items processed
- Tests publish/unpublish/approve/reject/delete
- 50 iterations, ~405 assertions

**Property 20: Partial failure reporting** (`BulkPartialFailurePropertyTest.php`)
- Validates failure details
- Tests authorization failures
- 200 iterations, ~3,164 assertions

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

### Browser Tests (Playwright)

```typescript
test('bulk actions workflow', async ({ page }) => {
    await page.goto('/admin/posts');
    
    // Select items
    await page.check('[aria-label="Select all items on this page"]');
    
    // Verify count
    await expect(page.locator('.bulk-actions')).toContainText('15 selected');
    
    // Perform action
    await page.click('button:has-text("Publish")');
    
    // Verify feedback
    await expect(page.locator('.notification')).toContainText('published successfully');
});
```

## Security Considerations

### Authorization

Always check policies before bulk operations:

```php
public function bulkDelete()
{
    $ids = $this->getSelectedIds();
    
    // Filter by policy
    $deletable = Post::whereIn('id', $ids)
        ->get()
        ->filter(fn($post) => $this->authorize('delete', $post))
        ->pluck('id');
    
    Post::whereIn('id', $deletable)->delete();
}
```

### Input Validation

IDs are normalized to prevent injection:

```php
// URL: ?selected[]=1&selected[]='; DROP TABLE posts--
// Result: [1, 0] (string cast to 0, filtered out)
```

### Rate Limiting

Consider rate limiting bulk operations:

```php
use Illuminate\Support\Facades\RateLimiter;

public function bulkPublish()
{
    $key = 'bulk-publish:' . auth()->id();
    
    if (RateLimiter::tooManyAttempts($key, 5)) {
        $this->dispatch('notify', 'Too many requests', 'error');
        return;
    }
    
    RateLimiter::hit($key, 60);
    
    // Process bulk action...
}
```

## Accessibility

### Keyboard Navigation

- Tab through checkboxes
- Space to toggle selection
- Enter to trigger bulk actions

### Screen Readers

```blade
<input 
    type="checkbox"
    wire:model.live="selectAll"
    aria-label="Select all {{ $posts->total() }} posts on this page"
    aria-describedby="bulk-actions-help"
/>

<div id="bulk-actions-help" class="sr-only">
    Use checkboxes to select items, then choose a bulk action
</div>
```

### Focus Management

```blade
@if($this->selectedCount > 0)
    <div 
        class="bulk-actions" 
        role="region" 
        aria-live="polite"
        aria-label="Bulk actions toolbar"
    >
        <span>{{ $this->selectedCount }} items selected</span>
    </div>
@endif
```

## Troubleshooting

### Selection Not Persisting

**Issue**: Selections cleared on page change

**Solution**: Ensure `setCurrentPageIds()` called after pagination:

```php
public function updatedPage()
{
    $posts = $this->getPosts();
    $this->setCurrentPageIds($posts->pluck('id'));
}
```

### Select All Not Working

**Issue**: Select all checkbox doesn't select items

**Solution**: Verify `wire:model.live` on checkbox and `currentPageIds` set:

```blade
<input type="checkbox" wire:model.live="selectAll" />
```

### Duplicate Selections

**Issue**: Same ID appears multiple times in URL

**Solution**: Use `getSelectedIds()` which normalizes automatically:

```php
// ✅ Good
$ids = $this->getSelectedIds();

// ❌ Bad
$ids = $this->selected;
```

## Related Documentation

- [Admin Configuration](ADMIN_CONFIGURATION.md) - Bulk action limits and settings
- [Bulk Actions Property Tests Index](../../tests/Unit/BULK_ACTIONS_PROPERTY_TESTS_INDEX.md)
- [Bulk Selection Display Testing](../../tests/Unit/BULK_SELECTION_DISPLAY_TESTING.md)
- [Livewire Traits Guide](../livewire/LIVEWIRE_TRAITS_GUIDE.md)

## Changelog

See [Admin Changelogs](changelogs/) for version history.
