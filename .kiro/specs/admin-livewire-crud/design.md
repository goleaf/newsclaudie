# Design Document

## Overview

This design transforms the admin portal into a fully Livewire-powered CRUD interface, eliminating traditional controller-based forms and providing a modern, reactive admin experience. The architecture leverages Laravel Livewire's component-based approach with Volt single-file components for rapid development, real-time validation, and seamless user interactions without page reloads.

The system will provide comprehensive CRUD operations for Posts, Categories, Comments, and Users through a unified interface pattern that includes:
- Modal-based create/edit workflows
- Inline editing capabilities
- Real-time search and filtering
- Bulk action support
- Sortable data tables
- Optimistic UI updates

### Context & Success Criteria

- Keep 95% of admin operations inline or modal-based with no full page reloads.
- Median Livewire interaction latency under 400ms with explicit loading/error/rollback states.
- Accessibility-first: keyboard navigation through tables and modals, focus traps in place, ARIA labels for all actionable elements.
- URL-driven state (filters/search/sort/pagination) preserved for shareable views; reset flows documented.
- Localization-ready: no hardcoded strings; reuse translations from `lang` files across actions and flash messages.

## Architecture

### Component Structure

The admin interface follows a hierarchical Livewire component architecture:

```
Admin Portal (Livewire Layout)
├── Posts Management
│   ├── PostsIndex (Volt Component)
│   ├── PostForm (Modal Volt Component)
│   └── PostRow (Inline Edit Component)
├── Categories Management
│   ├── CategoriesIndex (Volt Component)
│   ├── CategoryForm (Modal Volt Component)
│   └── CategoryRow (Inline Edit Component)
├── Comments Management
│   ├── CommentsIndex (Volt Component)
│   ├── CommentForm (Modal Volt Component)
│   └── CommentRow (Inline Edit Component)
└── Users Management
    ├── UsersIndex (Volt Component)
    ├── UserForm (Modal Volt Component)
    └── UserRow (Inline Edit Component)
```

### Technology Stack

- **Laravel Livewire 3.x**: Full-stack framework for reactive components
- **Livewire Volt**: Single-file component API for rapid development
- **Alpine.js**: Client-side interactivity for modals and UI enhancements
- **Tailwind CSS**: Utility-first styling framework
- **Laravel Validation**: Server-side validation with real-time feedback

### Design Patterns

1. **Repository Pattern**: Not required - Eloquent models provide sufficient abstraction
2. **Component Composition**: Reusable traits for common behaviors (pagination, search, bulk actions)
3. **Event-Driven Updates**: Livewire events for cross-component communication
4. **Optimistic UI**: Immediate UI updates with server confirmation
5. **Query String Persistence**: URL state management for bookmarkable filters

## Components and Interfaces

### Shared Traits

#### ManagesPerPage (Existing)
Already implemented in `app/Livewire/Concerns/ManagesPerPage.php`. Provides:
- Configurable per-page options
- Query string persistence
- Automatic page reset on per-page change

#### ManagesBulkActions (New)
```php
trait ManagesBulkActions
{
    public array $selected = [];
    public bool $selectAll = false;
    
    public function updatedSelectAll(bool $value): void
    public function toggleSelection(int $id): void
    public function clearSelection(): void
    public function getSelectedCountProperty(): int
}
```

#### ManagesSearch (New)
```php
trait ManagesSearch
{
    public ?string $search = null;
    
    public function updatingSearch(): void
    public function clearSearch(): void
    protected function applySearch(Builder $query, array $searchableFields): Builder
}
```

#### ManagesSorting (New)
```php
trait ManagesSorting
{
    public ?string $sortField = null;
    public string $sortDirection = 'asc';
    
    public function sortBy(string $field): void
    protected function applySorting(Builder $query): Builder
}
```

### Index Components

Each resource (Posts, Categories, Comments, Users) will have an index component following this structure:

```php
// Example: PostsIndex
use WithPagination;
use ManagesPerPage;
use ManagesBulkActions;
use ManagesSearch;
use ManagesSorting;

new class extends Component {
    // Traits provide common functionality
    
    // Resource-specific filters
    public ?string $statusFilter = null;
    public ?int $categoryFilter = null;
    
    // Modal state
    public bool $showModal = false;
    public ?int $editingId = null;
    
    // Methods
    public function openCreateModal(): void
    public function openEditModal(int $id): void
    public function closeModal(): void
    public function delete(int $id): void
    public function bulkDelete(): void
    public function with(): array
}
```

### Form Components

Modal-based forms for create/edit operations:

```php
// Example: PostForm
new class extends Component {
    public ?Post $post = null;
    public bool $isEditing = false;
    
    // Form fields
    public string $title = '';
    public string $slug = '';
    public string $body = '';
    public ?string $description = null;
    public ?string $featured_image = null;
    public array $tags = [];
    public array $selectedCategories = [];
    public ?string $published_at = null;
    
    // State
    public bool $slugManuallyEdited = false;
    
    // Lifecycle
    public function mount(?Post $post = null): void
    public function rules(): array
    public function messages(): array
    
    // Computed properties
    public function updatedTitle(string $value): void
    public function updatedSlug(string $value): void
    
    // Actions
    public function save(): void
    public function cancel(): void
}
```

### Inline Edit Components

For quick edits directly in table rows:

```php
// Example: PostRow
new class extends Component {
    public Post $post;
    public bool $editing = false;
    public string $editField = '';
    public mixed $editValue = null;
    
    public function startEdit(string $field): void
    public function saveEdit(): void
    public function cancelEdit(): void
    public function togglePublished(): void
}
```

## Data Models

### Post Model (Existing - Enhanced)
```php
class Post extends Model
{
    protected $fillable = [
        'user_id', 'title', 'slug', 'body', 'description',
        'featured_image', 'tags', 'published_at'
    ];
    
    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
    ];
    
    // Relationships
    public function author(): BelongsTo
    public function comments(): HasMany
    public function categories(): BelongsToMany
    
    // Scopes
    public function scopePublished(Builder $query): Builder
    public function scopeDraft(Builder $query): Builder
    public function scopeSearch(Builder $query, string $term): Builder
    
    // Methods
    public function isPublished(): bool
    public static function generateUniqueSlug(string $title): string
}
```

### Category Model (Existing)
```php
class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description'];
    
    public function posts(): BelongsToMany
}
```

### Comment Model (Existing - Enhanced)
```php
class Comment extends Model
{
    protected $fillable = ['user_id', 'post_id', 'content', 'status'];
    
    protected $casts = [
        'status' => CommentStatus::class,
    ];
    
    // Relationships
    public function user(): BelongsTo
    public function post(): BelongsTo
    
    // Scopes
    public function scopeApproved(Builder $query): Builder
    public function scopePending(Builder $query): Builder
    public function scopeRejected(Builder $query): Builder
    public function scopeWithStatus(Builder $query, ?CommentStatus $status): Builder
    
    // Methods
    public function isApproved(): bool
    public function isPending(): bool
    public function isRejected(): bool
}
```

### User Model (Existing)
```php
class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password'];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_author' => 'boolean',
        'is_banned' => 'boolean',
    ];
    
    public function posts(): HasMany
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*


### Core CRUD Properties

Property 1: Data persistence round-trip
*For any* resource (Post, Category, Comment, User) and any valid data, creating or updating the resource should result in the data being persisted to the database and displayed correctly in the table view
**Validates: Requirements 1.4, 2.5, 3.3, 4.2, 5.3, 11.3**

Property 2: Deletion removes resource
*For any* resource (Post, Category, Comment, User), deleting the resource should remove it from the database and the table display
**Validates: Requirements 1.5, 2.6, 3.5, 4.5**

Property 3: Relationship synchronization
*For any* post and any set of categories, assigning categories to the post should correctly sync the many-to-many relationship in both directions
**Validates: Requirements 1.7, 11.3, 11.5**

### Validation Properties

Property 4: Invalid input rejection
*For any* form field and any invalid input, submitting the form should display field-specific error messages and prevent data persistence
**Validates: Requirements 1.3, 2.4, 5.2, 6.3, 10.1, 10.4**

Property 5: Error clearing on correction
*For any* form field with a validation error, correcting the input to a valid value should clear the error message for that field
**Validates: Requirements 10.2**

Property 6: Validation success enables submission
*For any* form with all fields containing valid data, the system should remove all error indicators and allow form submission
**Validates: Requirements 10.5**

Property 7: Slug format validation
*For any* category slug input, the system should validate that the slug matches the format `/^[a-z0-9]+(?:-[a-z0-9]+)*$/` and reject invalid formats
**Validates: Requirements 2.4**

Property 8: Uniqueness validation
*For any* category with a slug that already exists in the database, attempting to save should fail with a uniqueness validation error
**Validates: Requirements 2.5**

Property 9: Email uniqueness validation
*For any* user with an email that already exists in the database, attempting to create the user should fail with an email uniqueness validation error
**Validates: Requirements 4.2**

### Search and Filtering Properties

Property 10: Search filtering accuracy
*For any* search term and any resource collection, the filtered results should only include items where the search term appears in searchable fields
**Validates: Requirements 4.6, 7.1**

Property 11: Status filter accuracy
*For any* comment status filter value, the filtered results should only include comments with that status
**Validates: Requirements 3.2, 7.2**

Property 12: Combined filter intersection
*For any* combination of multiple filters, the results should only include items that match all active filter criteria
**Validates: Requirements 7.3**

Property 13: Filter reset completeness
*For any* resource collection with active filters, clearing all filters should display the complete unfiltered collection
**Validates: Requirements 7.4**

Property 14: URL query string persistence
*For any* search, filter, or sort parameter change, the URL query string should be updated to reflect the current state
**Validates: Requirements 7.5, 9.4**

### Sorting Properties

Property 15: Column sort ordering
*For any* sortable column, clicking the column header should sort the table by that column in ascending order, and clicking again should toggle to descending order
**Validates: Requirements 9.1, 9.2**

Property 16: Sort state restoration
*For any* sorted table state persisted in the URL, navigating away and returning should restore the same sort order
**Validates: Requirements 9.5**

### Bulk Action Properties

Property 17: Bulk selection accuracy
*For any* set of selected table rows, the bulk actions toolbar should display and show the correct count of selected items
**Validates: Requirements 8.1, 8.4**

Property 18: Select all page scope
*For any* paginated table, clicking "select all" should select only the items visible on the current page
**Validates: Requirements 8.2**

Property 19: Bulk operation completeness
*For any* bulk action performed on a set of selected items, all selected items should be processed and the operation should complete successfully
**Validates: Requirements 8.3, 8.4**

Property 20: Bulk operation partial failure reporting
*For any* bulk action where some items fail to process, the system should display which items failed and the reason for each failure
**Validates: Requirements 8.5**

### UI State Properties

Property 21: Inline edit cancellation preservation
*For any* inline edit operation, canceling the edit should restore the original value without persisting any changes
**Validates: Requirements 5.4**

Property 22: Modal form reset on close
*For any* modal form, closing the modal should reset all form fields and clear all validation errors
**Validates: Requirements 6.5**

Property 23: Modal persistence on validation error
*For any* modal form submitted with invalid data, the modal should remain open and display validation errors
**Validates: Requirements 6.3**

Property 24: Successful save modal closure
*For any* modal form submitted with valid data, the save should succeed, the modal should close, and the table should refresh with updated data
**Validates: Requirements 6.4**

### Auto-generation Properties

Property 25: Slug auto-generation from name
*For any* category name input, if the slug has not been manually edited, the system should automatically generate a slug from the name
**Validates: Requirements 2.3**

Property 26: Manual slug edit stops auto-generation
*For any* category form where the slug is manually edited, subsequent changes to the name should not update the slug
**Validates: Requirements 2.4**

### Status Management Properties

Property 27: Publication status toggle
*For any* post, toggling the publication status should update the published_at field and reflect the change in the table display
**Validates: Requirements 1.6**

Property 28: Comment status update
*For any* comment, changing the approval status should update the status field and reflect the change in the display
**Validates: Requirements 3.4**

Property 29: User role flag updates
*For any* user, editing the is_admin or is_author flags should persist the changes and update the role badges in the table
**Validates: Requirements 4.3**

Property 30: User ban status update
*For any* user, changing the is_banned status should persist the change and update the account status display
**Validates: Requirements 4.4**

### Count and Display Properties

Property 31: Category post count accuracy
*For any* category, the displayed post count should equal the actual number of posts associated with that category
**Validates: Requirements 2.1, 2.7**

Property 32: Comment deletion count update
*For any* comment deletion, the post's comment count should be decremented by one
**Validates: Requirements 3.5**

Property 33: Category badge display
*For any* post with associated categories, the table view should display badges for all associated categories
**Validates: Requirements 1.7, 11.4**

### Optimistic UI Properties

Property 34: Optimistic UI immediate update
*For any* user action, the UI should update immediately before receiving server confirmation
**Validates: Requirements 12.1**

Property 35: Optimistic UI persistence on success
*For any* user action that succeeds on the server, the optimistic UI update should be maintained without reverting
**Validates: Requirements 12.2**

Property 36: Optimistic UI reversion on failure
*For any* user action that fails on the server, the optimistic UI update should be reverted and an error message should be displayed
**Validates: Requirements 12.3**

Property 37: Loading indicator display on latency
*For any* action where network latency exceeds 500 milliseconds, a loading indicator should be displayed
**Validates: Requirements 12.4**

Property 38: Sequential action processing
*For any* queue of multiple actions, the system should process them sequentially and update the UI for each action in order
**Validates: Requirements 12.5**

## Error Handling

### Validation Errors

All form submissions will be validated both client-side (for immediate feedback) and server-side (for security). Validation errors will be displayed:
- Adjacent to the specific field that failed validation
- With clear, actionable error messages
- In real-time as the user types (with debouncing)

### Server Errors

Server-side errors (500, 503, etc.) will be caught and displayed to the user with:
- A user-friendly error message
- The option to retry the action
- Automatic logging for debugging

### Network Errors

Network failures will be handled with:
- Automatic retry with exponential backoff
- Clear indication to the user that the action is pending
- Fallback to error state after maximum retries

### Optimistic Update Failures

When optimistic updates fail:
1. Revert the UI to the previous state
2. Display an error message explaining what went wrong
3. Optionally offer to retry the action

### Bulk Action Failures

When bulk actions partially fail:
1. Complete processing of all items
2. Display success count and failure count
3. Show detailed error messages for each failed item
4. Allow the user to retry failed items

## Testing Strategy

### Unit Testing

Unit tests will cover:
- Model methods and scopes
- Validation rules
- Slug generation logic
- Relationship synchronization
- Query builders for search and filtering

Example unit tests:
- `Post::generateUniqueSlug()` generates unique slugs
- Category slug validation regex works correctly
- Comment status scopes filter correctly
- User role flag updates persist

### Property-Based Testing

Property-based tests will verify universal properties across all inputs using **Pest PHP** with the **Pest Property Testing** plugin. Each property-based test will:
- Run a minimum of 100 iterations
- Generate random valid and invalid inputs
- Verify the property holds for all generated inputs
- Be tagged with a comment referencing the design document property

Example property-based tests:
- **Property 1**: For any valid post data, creating and retrieving should return equivalent data
- **Property 4**: For any invalid email format, user creation should fail with validation error
- **Property 10**: For any search term, filtered results should only contain matching items
- **Property 15**: For any sortable column, sorting should order items correctly

### Integration Testing

Integration tests will verify:
- Complete user workflows (create → edit → delete)
- Cross-component communication via Livewire events
- Modal open → edit → save → close cycles
- Bulk action workflows
- Search + filter + sort combinations

### Browser Testing

Browser tests using Laravel Dusk will verify:
- Modal animations and transitions
- Inline editing UI interactions
- Drag-and-drop functionality (if implemented)
- Keyboard navigation
- Accessibility compliance

## Performance Considerations

### Database Query Optimization

- Use eager loading for relationships (`with()`, `withCount()`)
- Index frequently searched and sorted columns
- Implement query result caching for expensive operations
- Use database-level pagination

### Livewire Optimization

- Use `wire:model.live.debounce` for search inputs (300ms debounce)
- Implement lazy loading for heavy components
- Use `wire:key` for list items to optimize DOM diffing
- Minimize component re-renders with targeted property updates

### Frontend Optimization

- Lazy load modal components
- Use Alpine.js for simple client-side interactions
- Implement virtual scrolling for large lists (if needed)
- Optimize image loading with lazy loading

### Caching Strategy

- Cache category lists (rarely change)
- Cache user role lookups
- Cache post counts for categories
- Invalidate caches on relevant updates

## Security Considerations

### Authorization

- All admin routes protected by `access-admin` gate
- Per-action authorization checks in Livewire components
- CSRF protection via Livewire's built-in mechanisms
- Rate limiting on bulk actions

### Input Validation

- Server-side validation for all inputs
- SQL injection prevention via Eloquent ORM
- XSS prevention via Blade escaping
- File upload validation (if implemented)

### Data Sanitization

- Sanitize user input before display
- Validate and sanitize slugs
- Strip dangerous HTML from rich text fields
- Validate relationship IDs before syncing

## Deployment Considerations

### Database Migrations

No new migrations required - all existing tables support the required functionality:
- `posts` table has all necessary fields
- `categories` table exists with proper structure
- `comments` table has status enum
- `users` table has role and ban flags
- `category_post` pivot table exists

### Configuration

Add to `config/interface.php`:
```php
'admin' => [
    'per_page_default' => 25,
    'per_page_options' => [10, 25, 50, 100],
    'search_debounce_ms' => 300,
    'optimistic_ui_enabled' => true,
    'bulk_action_limit' => 100,
],
```

### Asset Compilation

- Ensure Tailwind CSS includes admin component classes
- Compile Alpine.js with Livewire
- Optimize JavaScript bundle size

### Monitoring

- Log all bulk actions for audit trail
- Monitor Livewire component render times
- Track validation error rates
- Alert on high failure rates for optimistic updates
