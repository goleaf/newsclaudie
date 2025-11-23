# Optimistic UI Implementation

This document describes the enhanced optimistic UI implementation for the admin interface.

## Overview

The optimistic UI system provides immediate feedback to users while actions are being processed on the server. If an action fails, the UI automatically reverts to its previous state and displays an error message.

## Features

### 1. Loading Indicators with Delay (500ms)

All actions show loading indicators after 500ms to provide feedback for slower operations without flickering on fast connections.

**Implementation:**
```blade
<x-admin.optimistic-action target="saveCategory">
    {{ __('admin.categories.form.create') }}
</x-admin.optimistic-action>
```

Or using Livewire directly:
```blade
<span wire:loading.remove wire:target="saveCategory">Save</span>
<span wire:loading.delay.500ms wire:target="saveCategory">
    <svg class="animate-spin">...</svg>
    Saving...
</span>
```

### 2. Error Handling with Reversion

When server actions fail, the system:
- Reverts UI changes automatically
- Displays error messages
- Maintains user context

**Example:**
```php
// In Livewire component
public function togglePublished(int $postId): void
{
    try {
        $post = Post::findOrFail($postId);
        $this->authorize('update', $post);
        
        $post->forceFill(['published_at' => now()])->save();
        
        session()->flash('status', __('admin.posts.published'));
    } catch (\Exception $e) {
        session()->flash('error', __('admin.action_failed'));
        // Livewire will automatically revert the UI
    }
}
```

### 3. Action Feedback Component

Use the action feedback component for consistent error/success messaging:

```blade
<x-admin.action-feedback 
    type="success" 
    :message="session('status')"
    :auto-hide="true"
    :hide-delay="5000"
/>

<x-admin.action-feedback 
    type="error" 
    :message="session('error')"
/>
```

**Props:**
- `type`: success, error, warning, info
- `message`: The message to display
- `dismissible`: Allow manual dismissal (default: true)
- `autoHide`: Auto-hide after delay (default: false)
- `hideDelay`: Delay in milliseconds (default: 5000)

### 4. Sequential Action Processing

For bulk operations that need to be processed sequentially:

```javascript
// Using the OptimisticUIManager
window.optimisticUI.queueAction(async () => {
    await Livewire.find(componentId).call('bulkPublish');
});

window.optimisticUI.queueAction(async () => {
    await Livewire.find(componentId).call('bulkDelete');
});
```

### 5. Alpine.js Integration

For custom optimistic updates in Alpine components:

```html
<div x-data="window.optimisticComponent()">
    <button @click="optimisticUpdate(
        'toggle-status',
        () => { status = !status },
        () => $wire.toggleStatus(id),
        {
            onSuccess: () => console.log('Success!'),
            onFailure: (error) => console.error('Failed:', error)
        }
    )">
        Toggle Status
    </button>
</div>
```

## Implementation Details

### TypeScript Module

The `admin-optimistic-ui.ts` module provides:

- `OptimisticUIManager`: Global manager for tracking pending actions
- `optimisticComponent()`: Alpine.js data component for optimistic updates

### Blade Components

1. **optimistic-action**: Wraps action buttons with loading states
2. **action-feedback**: Displays success/error messages with auto-hide

### Livewire Integration

All admin Livewire components use:
- `wire:loading.delay.500ms` for loading indicators
- `session()->flash()` for success/error messages
- Automatic UI reversion on validation errors

## Best Practices

### 1. Always Use Delays

Use `wire:loading.delay.500ms` instead of `wire:loading` to prevent flickering:

```blade
{{-- Good --}}
<span wire:loading.delay.500ms wire:target="save">Saving...</span>

{{-- Avoid --}}
<span wire:loading wire:target="save">Saving...</span>
```

### 2. Provide Clear Feedback

Always show what's happening:

```blade
<x-admin.optimistic-action target="deletePost" loading-text="{{ __('admin.deleting') }}">
    {{ __('admin.posts.action_delete') }}
</x-admin.optimistic-action>
```

### 3. Handle Errors Gracefully

Always catch exceptions and provide user-friendly messages:

```php
try {
    // Perform action
    $post->delete();
    session()->flash('status', __('admin.posts.deleted'));
} catch (\Exception $e) {
    session()->flash('error', __('admin.action_failed'));
    \Log::error('Failed to delete post', ['error' => $e->getMessage()]);
}
```

### 4. Use Bulk Action Feedback

For bulk operations, provide detailed feedback:

```php
$this->bulkFeedback = [
    'status' => empty($failures) ? 'success' : 'warning',
    'action' => 'published',
    'updated' => $updated,
    'total' => $total,
    'failures' => $failures,
];
```

## Testing

### Manual Testing

1. **Fast Actions**: Verify no loading indicator appears for actions < 500ms
2. **Slow Actions**: Verify loading indicator appears after 500ms
3. **Failed Actions**: Verify UI reverts and error message displays
4. **Bulk Actions**: Verify sequential processing and partial failure handling

### Automated Testing

Property-based tests validate:
- Optimistic UI immediate update (Property 34)
- Persistence on success (Property 35)
- Reversion on failure (Property 36)
- Loading indicators on latency (Property 37)
- Sequential action processing (Property 38)

## Requirements Validation

This implementation satisfies:

- **Requirement 12.1**: Optimistic UI updates before server response
- **Requirement 12.2**: UI maintained on server confirmation
- **Requirement 12.3**: UI reverted on server rejection with error message
- **Requirement 12.4**: Loading indicators for actions > 500ms
- **Requirement 12.5**: Sequential processing of queued actions

## Future Enhancements

Potential improvements:
- Retry mechanism for failed actions
- Offline action queuing
- Conflict resolution for concurrent edits
- Real-time collaboration indicators
