# Optimistic UI Quick Reference

## Common Patterns

### 1. Action Button with Loading State

```blade
<flux:button 
    wire:click="savePost"
    wire:loading.attr="disabled"
    wire:target="savePost"
>
    <x-admin.optimistic-action target="savePost">
        Save Post
    </x-admin.optimistic-action>
</flux:button>
```

### 2. Custom Loading Text

```blade
<x-admin.optimistic-action 
    target="deleteCategory" 
    loading-text="{{ __('admin.deleting') }}"
>
    Delete
</x-admin.optimistic-action>
```

### 3. Success/Error Messages

```blade
{{-- Success message with auto-hide --}}
<x-admin.action-feedback 
    type="success" 
    :message="session('status')"
    :auto-hide="true"
    :hide-delay="3000"
/>

{{-- Error message (stays until dismissed) --}}
<x-admin.action-feedback 
    type="error" 
    :message="session('error')"
/>
```

### 4. Bulk Action with Feedback

```php
// In Livewire component
public function bulkPublish(): void
{
    $updated = 0;
    $failures = [];
    
    foreach ($this->selected as $id) {
        try {
            $post = Post::findOrFail($id);
            $post->publish();
            $updated++;
        } catch (\Exception $e) {
            $failures[] = ['id' => $id, 'reason' => $e->getMessage()];
        }
    }
    
    $this->bulkFeedback = [
        'status' => empty($failures) ? 'success' : 'warning',
        'updated' => $updated,
        'total' => count($this->selected),
        'failures' => $failures,
    ];
}
```

```blade
{{-- Display bulk feedback --}}
@if ($bulkFeedback)
    <x-admin.action-feedback 
        :type="$bulkFeedback['status']"
    >
        <p>Updated {{ $bulkFeedback['updated'] }} of {{ $bulkFeedback['total'] }} items.</p>
        
        @if (!empty($bulkFeedback['failures']))
            <details class="mt-2">
                <summary>{{ count($bulkFeedback['failures']) }} failed</summary>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($bulkFeedback['failures'] as $failure)
                        <li>{{ $failure['reason'] }}</li>
                    @endforeach
                </ul>
            </details>
        @endif
    </x-admin.action-feedback>
@endif
```

### 5. Sequential Actions (JavaScript)

```javascript
// Queue multiple actions to run sequentially
window.optimisticUI.queueAction(async () => {
    await Livewire.find(componentId).call('bulkPublish');
});

window.optimisticUI.queueAction(async () => {
    await Livewire.find(componentId).call('refreshData');
});
```

### 6. Custom Alpine Component

```html
<div x-data="{
    ...window.optimisticComponent(),
    
    captureState() {
        return { status: this.status };
    },
    
    revertState(actionId, state) {
        this.status = state.status;
        this.pendingActions.delete(actionId);
    }
}">
    <button @click="optimisticUpdate(
        'toggle-' + id,
        () => { status = !status },
        () => $wire.toggleStatus(id),
        {
            onSuccess: () => $dispatch('status-changed'),
            onFailure: (error) => alert('Failed: ' + error)
        }
    )">
        Toggle Status
    </button>
</div>
```

## Livewire Component Pattern

```php
public function performAction(int $id): void
{
    try {
        // Authorize
        $this->authorize('action', $model);
        
        // Perform action
        $model = Model::findOrFail($id);
        $model->doSomething();
        
        // Success feedback
        session()->flash('status', __('admin.action_success'));
        
        // Refresh if needed
        $this->dispatch('model-updated');
        
    } catch (AuthorizationException $e) {
        session()->flash('error', __('admin.unauthorized'));
    } catch (\Exception $e) {
        session()->flash('error', __('admin.action_failed'));
        \Log::error('Action failed', ['error' => $e->getMessage()]);
    }
}
```

## Checklist for New Actions

- [ ] Add `wire:loading.delay.500ms` for loading indicator
- [ ] Add `wire:loading.attr="disabled"` to prevent double-clicks
- [ ] Use `session()->flash()` for success/error messages
- [ ] Display feedback with `<x-admin.action-feedback>`
- [ ] Wrap button text with `<x-admin.optimistic-action>`
- [ ] Handle exceptions and provide user-friendly messages
- [ ] Log errors for debugging
- [ ] Test with slow network (DevTools throttling)

## Common Mistakes to Avoid

❌ **Don't**: Use `wire:loading` without delay
```blade
<span wire:loading>Loading...</span>
```

✅ **Do**: Use delay to prevent flickering
```blade
<span wire:loading.delay.500ms>Loading...</span>
```

---

❌ **Don't**: Forget to disable buttons during loading
```blade
<button wire:click="save">Save</button>
```

✅ **Do**: Disable to prevent double-clicks
```blade
<button wire:click="save" wire:loading.attr="disabled">Save</button>
```

---

❌ **Don't**: Show generic error messages
```php
session()->flash('error', 'Error');
```

✅ **Do**: Provide helpful context
```php
session()->flash('error', __('admin.posts.delete_failed'));
```

---

❌ **Don't**: Ignore exceptions
```php
$post->delete();
```

✅ **Do**: Handle and log errors
```php
try {
    $post->delete();
    session()->flash('status', __('admin.posts.deleted'));
} catch (\Exception $e) {
    session()->flash('error', __('admin.action_failed'));
    \Log::error('Delete failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
}
```

## Testing Tips

1. **Test with slow network**: Use Chrome DevTools → Network → Throttling → Slow 3G
2. **Test error scenarios**: Temporarily throw exceptions to verify reversion
3. **Test bulk actions**: Verify partial failures are handled correctly
4. **Test sequential processing**: Queue multiple actions and verify order
5. **Test auto-hide**: Verify messages disappear after configured delay

## Performance Considerations

- Loading indicators only appear after 500ms (no flickering on fast connections)
- Alpine.js transitions are GPU-accelerated
- Livewire automatically batches updates
- Action queue processes sequentially to avoid race conditions

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Requires JavaScript enabled
- Graceful degradation: actions still work without JS, just no optimistic updates
