# Admin Comments API Documentation

## Overview

This document provides API-level documentation for the admin comments Livewire component, including all public methods, properties, and their usage.

**Component:** `resources/views/livewire/admin/comments/index.blade.php`  
**Type:** Livewire Volt Component  
**Route:** `/admin/comments`  
**Authorization:** Requires `access-admin` permission  
**Last Updated:** 2025-11-23

## Component Class

```php
new class extends Component {
    use AuthorizesRequests;
    use ManagesBulkActions;
    use ManagesPerPage;
    use ManagesSearch;
    use ManagesSorting;
    use WithPagination;
}
```

## Public Properties

### URL-Persisted Properties

#### `$page`
```php
#[Url(except: 1)]
public int $page = 1;
```
- **Type:** `int`
- **Default:** `1`
- **URL Parameter:** `page`
- **Purpose:** Current pagination page number
- **Persistence:** Excluded from URL when value is 1

#### `$status`
```php
public ?string $status = null;
```
- **Type:** `string|null`
- **Default:** `null`
- **URL Parameter:** `status`
- **Purpose:** Filter comments by status (Pending, Approved, Rejected)
- **Valid Values:** `'pending'`, `'approved'`, `'rejected'`, `null` (all)
- **Persistence:** Excluded from URL when null

### Component State Properties

#### `$bulkFeedback`
```php
public ?array $bulkFeedback = null;
```
- **Type:** `array|null`
- **Default:** `null`
- **Purpose:** Stores bulk action results for user feedback
- **Structure:**
  ```php
  [
      'status' => 'success|warning',
      'action' => 'approved|rejected|deleted',
      'total' => int,
      'updated' => int,
      'failures' => [
          ['id' => int, 'title' => string, 'reason' => string],
          // ...
      ]
  ]
  ```

#### `$editingCommentId`
```php
public ?int $editingCommentId = null;
```
- **Type:** `int|null`
- **Default:** `null`
- **Purpose:** ID of comment currently being edited inline
- **Behavior:** Only one comment can be edited at a time

#### `$editingContent`
```php
public string $editingContent = '';
```
- **Type:** `string`
- **Default:** `''`
- **Purpose:** Temporary storage for comment content during inline editing
- **Validation:** Required, max 1024 characters

#### `$editingStatus`
```php
public ?string $editingStatus = null;
```
- **Type:** `string|null`
- **Default:** `null`
- **Purpose:** Temporary storage for comment status during inline editing
- **Valid Values:** `'pending'`, `'approved'`, `'rejected'`

## Public Methods

### Lifecycle Methods

#### `mount()`
```php
public function mount(): void
```
**Purpose:** Initialize component state and query string configuration

**Actions:**
- Authorizes admin access
- Merges query string configurations from traits
- Resolves initial sort field and direction

**Authorization:** Throws `AuthorizationException` if user lacks `access-admin` permission

**Example:**
```php
// Called automatically by Livewire on component load
// No manual invocation needed
```

### Filter Methods

#### `updatingStatus()`
```php
public function updatingStatus(): void
```
**Purpose:** Reset pagination when status filter changes

**Lifecycle:** Called before `$status` property is updated

**Behavior:** Resets to page 1 to avoid empty results

#### `updatedStatus($value)`
```php
public function updatedStatus($value): void
```
**Purpose:** Sanitize status value after update

**Parameters:**
- `$value` (mixed) - New status value from user input

**Behavior:** Validates and normalizes status to valid enum value or null

#### `clearFilters()`
```php
public function clearFilters(): void
```
**Purpose:** Reset all filters to default state

**Actions:**
- Clears search term
- Resets status filter to null
- Resets pagination to page 1

**Example:**
```blade
<flux:button wire:click="clearFilters">
    {{ __('admin.comments.filters.clear') }}
</flux:button>
```

### Comment Action Methods

#### `deleteComment(int $commentId)`
```php
public function deleteComment(int $commentId): void
```
**Purpose:** Delete a single comment

**Parameters:**
- `$commentId` (int) - ID of comment to delete

**Authorization:** Requires `access-admin` permission

**Actions:**
1. Finds comment or throws 404
2. Authorizes admin access
3. Cancels inline editing if active
4. Deletes comment
5. Updates post comment count
6. Flashes success message
7. Resets pagination

**Side Effects:**
- Updates `comments_count` on associated post
- Clears inline editing state if editing this comment

**Example:**
```blade
<flux:button 
    wire:click="deleteComment({{ $comment->id }})"
    wire:confirm="{{ __('admin.comments.confirm_delete') }}">
    {{ __('admin.comments.action_delete') }}
</flux:button>
```

#### `approveComment(int $commentId)`
```php
public function approveComment(int $commentId): void
```
**Purpose:** Approve a single comment

**Parameters:**
- `$commentId` (int) - ID of comment to approve

**Authorization:** Requires `access-admin` permission

**Actions:**
1. Finds comment or throws 404
2. Authorizes admin access
3. Updates status to `CommentStatus::Approved`
4. Flashes success message

**Example:**
```blade
<flux:button 
    wire:click="approveComment({{ $comment->id }})"
    color="green">
    {{ __('admin.comments.status.approved') }}
</flux:button>
```

#### `rejectComment(int $commentId)`
```php
public function rejectComment(int $commentId): void
```
**Purpose:** Reject a single comment

**Parameters:**
- `$commentId` (int) - ID of comment to reject

**Authorization:** Requires `access-admin` permission

**Actions:**
1. Finds comment or throws 404
2. Authorizes admin access
3. Updates status to `CommentStatus::Rejected`
4. Flashes success message

**Example:**
```blade
<flux:button 
    wire:click="rejectComment({{ $comment->id }})"
    color="amber">
    {{ __('admin.comments.status.rejected') }}
</flux:button>
```

### Inline Editing Methods

#### `startEditing(int $commentId)`
```php
public function startEditing(int $commentId): void
```
**Purpose:** Enter inline editing mode for a comment

**Parameters:**
- `$commentId` (int) - ID of comment to edit

**Authorization:** Requires `access-admin` permission

**Actions:**
1. Authorizes admin access
2. Finds comment or throws 404
3. Clears validation errors
4. Sets editing state properties
5. Loads comment data into editing fields

**State Changes:**
- `$editingCommentId` = comment ID
- `$editingContent` = comment content
- `$editingStatus` = comment status value

**Example:**
```blade
<flux:button wire:click="startEditing({{ $comment->id }})">
    {{ __('admin.comments.action_edit') }}
</flux:button>
```

#### `cancelEditing()`
```php
public function cancelEditing(): void
```
**Purpose:** Exit inline editing mode without saving

**Actions:**
- Resets all editing state properties
- Clears validation errors

**State Changes:**
- `$editingCommentId` = null
- `$editingContent` = ''
- `$editingStatus` = null

**Example:**
```blade
<flux:button wire:click="cancelEditing" variant="ghost">
    {{ __('admin.inline.cancel') }}
</flux:button>
```

#### `updateComment()`
```php
public function updateComment(): void
```
**Purpose:** Save inline edits to comment

**Authorization:** Requires `access-admin` permission

**Validation Rules:**
```php
[
    'editingContent' => ['required', 'string', 'max:1024'],
    'editingStatus' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
]
```

**Actions:**
1. Validates editing fields
2. Authorizes admin access
3. Finds comment or throws 404
4. Updates comment with validated data
5. Flashes success message
6. Resets editing state

**Example:**
```blade
<flux:button wire:click="updateComment" color="indigo">
    {{ __('admin.inline.save') }}
</flux:button>
```

### Bulk Action Methods

#### `bulkApprove()`
```php
public function bulkApprove(): void
```
**Purpose:** Approve multiple selected comments

**Authorization:** Requires `access-admin` permission for each comment

**Actions:**
1. Gets selected comment IDs
2. Loads comments in single query
3. Attempts to approve each comment
4. Tracks successes and failures
5. Sets bulk feedback for user
6. Clears selection on full success

**Feedback Structure:**
```php
[
    'status' => 'success|warning',
    'action' => 'approved',
    'total' => 5,
    'updated' => 4,
    'failures' => [
        ['id' => 123, 'title' => '#123', 'reason' => 'Not found'],
    ]
]
```

**Example:**
```blade
<flux:button 
    wire:click="bulkApprove"
    wire:confirm="{{ trans_choice('admin.comments.bulk_confirm_approve', $this->selectedCount) }}">
    {{ __('admin.comments.bulk_approve') }}
</flux:button>
```

#### `bulkReject()`
```php
public function bulkReject(): void
```
**Purpose:** Reject multiple selected comments

**Authorization:** Requires `access-admin` permission for each comment

**Behavior:** Similar to `bulkApprove()` but sets status to `Rejected`

**Example:**
```blade
<flux:button 
    wire:click="bulkReject"
    wire:confirm="{{ trans_choice('admin.comments.bulk_confirm_reject', $this->selectedCount) }}">
    {{ __('admin.comments.bulk_reject') }}
</flux:button>
```

#### `bulkDelete()`
```php
public function bulkDelete(): void
```
**Purpose:** Delete multiple selected comments

**Authorization:** Requires `access-admin` permission for each comment

**Actions:**
1. Gets selected comment IDs
2. Loads comments in single query
3. Attempts to delete each comment
4. Updates post comment counts for affected posts
5. Tracks successes and failures
6. Sets bulk feedback for user
7. Clears selection on full success
8. Resets pagination

**Side Effects:**
- Updates `comments_count` on all affected posts
- Resets pagination to page 1

**Example:**
```blade
<flux:button 
    wire:click="bulkDelete"
    wire:confirm="{{ trans_choice('admin.comments.bulk_confirm_delete', $this->selectedCount) }}"
    color="red">
    {{ __('admin.comments.bulk_delete') }}
</flux:button>
```

### Data Methods

#### `with()`
```php
public function with(): array
```
**Purpose:** Provide data to the Blade template

**Returns:**
```php
[
    'comments' => LengthAwarePaginator,  // Paginated comments
    'searchTerm' => string,               // Current search term
    'activeStatus' => string|null,        // Current status filter
    'isFiltered' => bool,                 // Whether any filters are active
]
```

**Query Optimization:**
- Eager loads `user` and `post` relationships
- Selects only required columns
- Applies search and status filters
- Applies sorting
- Paginates results

**Example Usage in Blade:**
```blade
@forelse ($comments as $comment)
    <tr>
        <td>{{ $comment->user?->name }}</td>
        <td>{{ $comment->post?->title }}</td>
        <td>{{ $comment->content }}</td>
    </tr>
@empty
    <tr><td colspan="3">No comments</td></tr>
@endforelse
```

## Private Methods

### `baseQuery(?array $filters = null): Builder`

**Purpose:** Build the base query for comments with filters and eager loading

**Parameters:**
- `$filters` (array|null) - Optional filters array with 'search' and 'status' keys

**Returns:** `Builder<Comment>` - Query builder instance

**Eager Loading:**
```php
->with(['user:id,name', 'post:id,title,slug,user_id'])
```

**Filters Applied:**
- Search: `WHERE content LIKE '%search%'`
- Status: `WHERE status = ?`
- Sorting: Applied via `ManagesSorting` trait

**Performance:**
- Prevents N+1 queries
- Loads only required columns
- Uses indexed columns for filtering

**See:** [ADMIN_COMMENTS_EAGER_LOADING.md](ADMIN_COMMENTS_EAGER_LOADING.md) for detailed explanation

### `applySort(Builder $query): Builder`

**Purpose:** Apply sorting to query based on current sort state

**Parameters:**
- `$query` (Builder) - Query builder instance

**Returns:** `Builder` - Query builder with sorting applied

**Sortable Columns:**
- `status` - Sort by comment status
- `created_at` - Sort by creation date (default)

**Default Sort:** `created_at DESC` (newest first)

### `resolvedFilters(): array`

**Purpose:** Get current filter values with sanitization

**Returns:**
```php
[
    'search' => string,      // Sanitized search term
    'status' => string|null, // Validated status or null
]
```

### `resetEditingState(): void`

**Purpose:** Clear all inline editing state

**Actions:**
- Resets `$editingCommentId`, `$editingContent`, `$editingStatus`
- Clears validation errors

### `hasSearch(): bool`

**Purpose:** Check if search filter is active

**Returns:** `bool` - True if search term is not empty

### `sanitizeStatus(?string $value): ?string`

**Purpose:** Validate and normalize status value

**Parameters:**
- `$value` (string|null) - Raw status value

**Returns:** `string|null` - Valid enum value or null

**Validation:** Checks against `CommentStatus` enum values

### `findComment(int $commentId): Comment`

**Purpose:** Find comment by ID or throw 404

**Parameters:**
- `$commentId` (int) - Comment ID

**Returns:** `Comment` - Comment model instance

**Throws:** `ModelNotFoundException` if comment not found

### `changeCommentStatus(int $commentId, CommentStatus $status): void`

**Purpose:** Change comment status (internal helper)

**Parameters:**
- `$commentId` (int) - Comment ID
- `$status` (CommentStatus) - New status enum value

**Authorization:** Requires `access-admin` permission

**Actions:**
1. Finds comment
2. Authorizes access
3. Updates status
4. Flashes success message

### `processBulkStatusChange(CommentStatus $status, string $action): void`

**Purpose:** Process bulk status change operation

**Parameters:**
- `$status` (CommentStatus) - Target status
- `$action` (string) - Action name for feedback ('approved', 'rejected')

**Behavior:**
- Loads all selected comments in single query
- Attempts status change on each
- Tracks failures with reasons
- Updates bulk feedback
- Keeps failed items selected

### `processBulkDelete(): void`

**Purpose:** Process bulk delete operation

**Behavior:**
- Loads all selected comments in single query
- Attempts delete on each
- Updates post comment counts
- Tracks failures with reasons
- Updates bulk feedback
- Keeps failed items selected
- Resets pagination

## Trait Methods

### From ManagesBulkActions

- `getSelectedIds(): array` - Get array of selected IDs
- `toggleSelection(int $id): void` - Toggle selection state
- `clearSelection(): void` - Clear all selections
- `setCurrentPageIds(Collection $ids): void` - Set available IDs for current page
- `$selectedCount` (computed) - Count of selected items
- `$selectAll` (property) - Select all checkbox state

### From ManagesPerPage

- `$perPage` (property) - Items per page
- `$perPageOptions` (computed) - Available per-page options
- `perPageQueryStringConfig(): array` - Query string config

### From ManagesSearch

- `$search` (property) - Search term
- `getSearchTerm(): string` - Get sanitized search term
- `clearSearch(): void` - Clear search term

### From ManagesSorting

- `$sortField` (property) - Current sort column
- `$sortDirection` (property) - Sort direction ('asc'|'desc')
- `sortBy(string $field): void` - Toggle sort on field
- `isSortedBy(string $field): bool` - Check if sorted by field
- `resolvedSort(): array` - Get validated sort state

## Events

### Emitted Events

None. Component uses Livewire's automatic reactivity.

### Listened Events

None. Component responds to user actions via wire:click.

## Query String Parameters

| Parameter | Property | Default | Purpose |
|-----------|----------|---------|---------|
| `page` | `$page` | 1 | Pagination page |
| `status` | `$status` | null | Status filter |
| `search` | `$search` | '' | Search term |
| `sort` | `$sortField` | 'created_at' | Sort column |
| `direction` | `$sortDirection` | 'desc' | Sort direction |
| `perPage` | `$perPage` | 20 | Items per page |

**Example URL:**
```
/admin/comments?status=pending&search=spam&sort=created_at&direction=desc&page=2&perPage=50
```

## Authorization

All methods require the `access-admin` permission. Authorization is checked via:

```php
$this->authorize('access-admin');
```

**Policy:** Defined in `app/Policies/CommentPolicy.php` (if exists) or gate definition

**Failure:** Throws `AuthorizationException` with 403 status

## Validation

### Inline Edit Validation

```php
[
    'editingContent' => ['required', 'string', 'max:1024'],
    'editingStatus' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
]
```

**Error Display:** Validation errors shown inline next to editing fields

**Clearing:** Errors cleared on `cancelEditing()` or successful save

## Performance Considerations

### Query Optimization

1. **Eager Loading:** Prevents N+1 queries
2. **Selective Columns:** Loads only required fields
3. **Indexed Filters:** Uses indexed columns for WHERE clauses
4. **Pagination:** Limits result set size

### Memory Optimization

1. **Pagination:** Processes limited records per page
2. **Selective Loading:** Reduces model memory footprint
3. **Bulk Operations:** Loads all selected items in single query

### Recommended Limits

- **Per Page:** 20-50 items (configurable)
- **Bulk Actions:** 100 items max (configurable)
- **Search:** Debounced 300ms

## Error Handling

### Common Errors

1. **ModelNotFoundException:** Comment not found
   - **HTTP Status:** 404
   - **User Message:** "Comment not found"

2. **AuthorizationException:** Insufficient permissions
   - **HTTP Status:** 403
   - **User Message:** "Unauthorized"

3. **ValidationException:** Invalid input
   - **HTTP Status:** 422
   - **User Message:** Field-specific errors

### Bulk Operation Failures

Partial failures are tracked and reported:

```php
[
    'failures' => [
        ['id' => 123, 'title' => '#123', 'reason' => 'Not found'],
        ['id' => 456, 'title' => '#456', 'reason' => 'Unauthorized'],
    ]
]
```

## Testing

### Unit Tests

```bash
php artisan test tests/Unit/CommentModelTest.php
```

### Feature Tests

```bash
php artisan test tests/Feature/AdminCommentsPageTest.php
```

### Browser Tests

```bash
php artisan test tests/Browser/AdminCommentsTest.php
```

## Related Documentation

- [Admin Comments Eager Loading](ADMIN_COMMENTS_EAGER_LOADING.md)
- [Livewire Traits Guide](../LIVEWIRE_TRAITS_GUIDE.md)
- [Admin Configuration](../ADMIN_CONFIGURATION.md)
- [Comment Model](../../app/Models/Comment.php)

## Changelog

### 2025-11-23
- Added `user_id` to post eager loading
- Enhanced DocBlock documentation
- Created comprehensive API documentation

---

**Last Updated:** 2025-11-23  
**Version:** 1.0.0  
**Maintainer:** Laravel Blog Application Team
