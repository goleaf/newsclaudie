# Volt Component Documentation Guide

This document provides comprehensive documentation for all Livewire Volt components in the admin interface.

## Table of Contents

- [Overview](#overview)
- [Component Structure](#component-structure)
- [Common Patterns](#common-patterns)
- [Component Reference](#component-reference)
- [Best Practices](#best-practices)

## Overview

All admin CRUD interfaces are built using Laravel Livewire Volt components. Volt provides a single-file component API that combines PHP logic and Blade templates in one file.

### Key Features

- **Single-file components**: Logic and view in one file
- **Reactive properties**: Automatic UI updates
- **Query string persistence**: Bookmarkable URLs
- **Real-time validation**: Immediate feedback
- **Inline editing**: Edit directly in tables
- **Modal workflows**: Create/edit without page navigation

## Component Structure

### Standard Volt Component Layout

```php
<?php
// 1. Use statements
use App\Livewire\Concerns\ManagesPerPage;
use Livewire\Volt\Component;
use Livewire\WithPagination;

// 2. Layout and title
layout('components.layouts.admin');
title(__('admin.resource.title'));

// 3. Component class
new class extends Component {
    // 4. Traits
    use WithPagination;
    use ManagesPerPage;
    
    // 5. Public properties
    public ?string $search = null;
    
    // 6. Protected properties
    protected $queryString = ['search'];
    
    // 7. Lifecycle methods
    public function mount(): void { }
    
    // 8. Action methods
    public function delete(int $id): void { }
    
    // 9. Computed properties
    public function getItemsProperty() { }
    
    // 10. Render method
    public function with(): array { }
};
?>

<!-- 11. Blade template -->
<div>
    <!-- Component markup -->
</div>
```

## Common Patterns

### Property Documentation

All public properties should be documented with their purpose and type:

```php
/**
 * The current search term for filtering results.
 * Persisted in URL query string as 'search' parameter.
 */
#[Url(except: '', as: 'search')]
public ?string $search = null;

/**
 * ID of the item currently being edited inline.
 * Null when no inline edit is active.
 */
public ?int $editingId = null;

/**
 * Whether the create/edit form modal is open.
 */
public bool $formOpen = false;
```

### Method Documentation

All public methods should document their purpose, parameters, and behavior:

```php
/**
 * Delete a category and update the UI.
 *
 * Validates admin authorization, cancels any active inline edits,
 * closes the form if editing this category, and resets pagination.
 *
 * @param  int  $categoryId  The ID of the category to delete
 * @return void
 */
public function deleteCategory(int $categoryId): void
{
    $this->authorize('access-admin');
    // Implementation...
}

/**
 * Start inline editing for a specific field.
 *
 * Loads the current value into editingValues and sets edit mode.
 * Only works for fields defined in isInlineField().
 *
 * @param  int  $categoryId  The ID of the item to edit
 * @param  string  $field  The field name to edit (e.g., 'name', 'slug')
 * @return void
 */
public function startEditing(int $categoryId, string $field): void
{
    // Implementation...
}
```

### Validation Rules Documentation

Document validation rules and their purpose:

```php
/**
 * Get validation rules for inline editing.
 *
 * Rules:
 * - name: Required, max 255 characters
 * - slug: Required, lowercase alphanumeric with hyphens, unique
 *
 * @return array<string, array<string>>
 */
protected function inlineRules(): array
{
    return [
        'editingValues.name' => ['required', 'string', 'max:255'],
        'editingValues.slug' => [
            'required',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('categories', 'slug')->ignore($this->editingId),
        ],
    ];
}
```

### Complex Logic Documentation

Add inline comments for complex logic:

```php
public function with(): array
{
    // Use default sort if none specified
    $sortField = $this->sortField ?: $this->defaultSortField();
    $sortDirection = $this->sortDirection ?: $this->defaultSortDirection();

    $searchTerm = trim($this->search ?? '');

    $query = Category::query()->withCount('posts');

    // Apply search across multiple fields
    if ($searchTerm !== '') {
        $query->where(function (Builder $q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('slug', 'LIKE', "%{$searchTerm}%")
                ->orWhere('description', 'LIKE', "%{$searchTerm}%");
        });
    }

    // Apply primary sort, then secondary sort for consistency
    $categories = $query
        ->orderBy($sortField, $sortDirection)
        ->when($sortField !== 'name', fn (Builder $query) => $query->orderBy('name'))
        ->when($sortField === 'name', fn (Builder $query) => $query->orderBy('id'))
        ->paginate($this->perPage)
        ->withQueryString();

    return [
        'categories' => $categories,
        'searchTerm' => $searchTerm,
    ];
}
```

## Component Reference

### Categories Index Component

**Location**: `resources/views/livewire/admin/categories/index.blade.php`

**Purpose**: Manage categories with inline editing, search, sorting, and form-based create/edit.

**Key Features**:
- Inline editing for name and slug fields
- Side-panel form for create/edit operations
- Real-time search across name, slug, and description
- Sortable columns (name, posts_count, updated_at)
- Auto-slug generation from name
- Manual slug editing with format validation

**Public Properties**:
```php
// Inline editing state
public ?int $editingId = null;              // ID of item being edited
public ?string $editingField = null;        // Field being edited ('name' or 'slug')
public array $editingValues = [];           // Current edit values

// Form state
public bool $formOpen = false;              // Whether form is visible
public ?int $formCategoryId = null;         // ID when editing (null when creating)
public string $formName = '';               // Form field: name
public string $formSlug = '';               // Form field: slug
public ?string $formDescription = null;     // Form field: description
public bool $formSlugManuallyEdited = false; // Whether slug was manually edited

// UI feedback
public ?string $statusMessage = null;       // Success/error message
public string $statusLevel = 'success';     // Message level ('success' or 'error')
```

**Key Methods**:
- `deleteCategory(int $categoryId)`: Delete a category
- `startEditing(int $categoryId, string $field)`: Begin inline edit
- `saveInlineEdit()`: Save inline edit changes
- `cancelInlineEdit()`: Cancel inline edit
- `startCreateForm()`: Open form for new category
- `startEditForm(int $categoryId)`: Open form to edit existing category
- `saveCategory()`: Save form (create or update)
- `updatedFormName(string $value)`: Auto-generate slug from name
- `updatedFormSlug(string $value)`: Mark slug as manually edited

**Validation**: Requirements 2.1-2.7

### Posts Index Component

**Location**: `resources/views/livewire/admin/posts/index.blade.php`

**Purpose**: Manage posts with filtering, bulk actions, and publication status toggle.

**Key Features**:
- Search across title, body, and description
- Filter by publication status (all, published, draft)
- Filter by category
- Bulk publish/unpublish actions
- Publication status toggle
- Category badge display

**Public Properties**:
```php
// Search and filters
public ?string $search = null;              // Search term
public ?string $statusFilter = null;        // 'published', 'draft', or null
public ?int $categoryFilter = null;         // Category ID or null

// Bulk actions
public array $selected = [];                // Selected post IDs
public bool $selectAll = false;             // Select all on current page

// Modal state
public bool $showModal = false;             // Whether modal is open
public ?int $editingId = null;              // ID when editing (null when creating)
```

**Key Methods**:
- `deletePost(int $postId)`: Delete a post
- `togglePublished(int $postId)`: Toggle publication status
- `bulkPublish()`: Publish selected posts
- `bulkUnpublish()`: Unpublish selected posts
- `bulkDelete()`: Delete selected posts
- `openCreateModal()`: Open modal for new post
- `openEditModal(int $postId)`: Open modal to edit existing post

**Validation**: Requirements 1.1-1.7

### Comments Index Component

**Location**: `resources/views/livewire/admin/comments/index.blade.php`

**Purpose**: Manage comments with status filtering, inline editing, and bulk actions.

**Key Features**:
- Search across content and author name
- Filter by status (all, approved, pending, rejected)
- Inline content editing
- Bulk approve/reject/delete actions
- Post context display

**Public Properties**:
```php
// Search and filters
public ?string $search = null;              // Search term
public ?string $statusFilter = null;        // 'approved', 'pending', 'rejected', or null

// Inline editing
public ?int $editingId = null;              // ID of comment being edited
public string $editingContent = '';         // Current edit value

// Bulk actions
public array $selected = [];                // Selected comment IDs
public bool $selectAll = false;             // Select all on current page
```

**Key Methods**:
- `deleteComment(int $commentId)`: Delete a comment
- `approveComment(int $commentId)`: Approve a comment
- `rejectComment(int $commentId)`: Reject a comment
- `startEditing(int $commentId)`: Begin inline edit
- `saveInlineEdit()`: Save inline edit changes
- `cancelInlineEdit()`: Cancel inline edit
- `bulkApprove()`: Approve selected comments
- `bulkReject()`: Reject selected comments
- `bulkDelete()`: Delete selected comments

**Validation**: Requirements 3.1-3.6

### Users Index Component

**Location**: `resources/views/livewire/admin/users/index.blade.php`

**Purpose**: Manage users with role and ban status controls.

**Key Features**:
- Search across name and email
- Role badge display (admin, author)
- Ban status indicator
- Modal-based create/edit
- Delete with content handling

**Public Properties**:
```php
// Search
public ?string $search = null;              // Search term

// Modal state
public bool $showModal = false;             // Whether modal is open
public ?int $editingId = null;              // ID when editing (null when creating)

// Form fields
public string $formName = '';               // Form field: name
public string $formEmail = '';              // Form field: email
public ?string $formPassword = null;        // Form field: password (optional for edit)
public bool $formIsAdmin = false;           // Form field: admin role
public bool $formIsAuthor = false;          // Form field: author role
public bool $formIsBanned = false;          // Form field: ban status
```

**Key Methods**:
- `deleteUser(int $userId)`: Delete a user
- `openCreateModal()`: Open modal for new user
- `openEditModal(int $userId)`: Open modal to edit existing user
- `saveUser()`: Save form (create or update)
- `toggleAdmin(int $userId)`: Toggle admin role
- `toggleAuthor(int $userId)`: Toggle author role
- `toggleBanned(int $userId)`: Toggle ban status

**Validation**: Requirements 4.1-4.6

## Best Practices

### 1. Property Naming

Use clear, descriptive names:

```php
// Good
public ?int $editingId = null;
public string $formName = '';
public bool $formSlugManuallyEdited = false;

// Avoid
public ?int $id = null;
public string $name = '';
public bool $edited = false;
```

### 2. Method Organization

Group related methods together:

```php
// Lifecycle methods
public function mount(): void { }
public function updated(string $property, mixed $value): void { }

// Action methods
public function delete(int $id): void { }
public function save(): void { }

// Inline editing methods
public function startEditing(int $id, string $field): void { }
public function saveInlineEdit(): void { }
public function cancelInlineEdit(): void { }

// Form methods
public function openForm(): void { }
public function closeForm(): void { }
public function saveForm(): void { }

// Validation methods
protected function rules(): array { }
protected function messages(): array { }

// Helper methods
protected function resetForm(): void { }
private function isInlineField(string $field): bool { }
```

### 3. Validation Messages

Use translation keys for all validation messages:

```php
protected function messages(): array
{
    return [
        'formName.required' => __('validation.category.name_required'),
        'formSlug.regex' => __('validation.category.slug_regex'),
        'formSlug.unique' => __('validation.category.slug_unique'),
    ];
}
```

### 4. Query String Configuration

Document query string parameters:

```php
/**
 * Query string configuration for URL persistence.
 *
 * Persisted parameters:
 * - perPage: Items per page (default: 20)
 * - search: Search term (default: empty)
 * - sort: Sort field (default: 'name')
 * - direction: Sort direction (default: 'asc')
 * - page: Current page (default: 1)
 */
protected $queryString = [
    'perPage' => ['except' => null],
    'search' => ['except' => ''],
    'sortField' => ['as' => 'sort', 'except' => 'name'],
    'sortDirection' => ['as' => 'direction', 'except' => 'asc'],
    'page' => ['except' => 1],
];
```

### 5. Authorization

Always authorize admin actions:

```php
public function deleteCategory(int $categoryId): void
{
    // Always authorize first
    $this->authorize('access-admin');
    
    // Then perform action
    $category = Category::findOrFail($categoryId);
    $category->delete();
}
```

### 6. Error Handling

Provide clear error messages:

```php
public function bulkDelete(): void
{
    if (AdminConfig::exceedsBulkActionLimit($this->selectedCount)) {
        $this->addError('selected', __('admin.bulk.limit_exceeded', [
            'limit' => AdminConfig::bulkActionLimit()
        ]));
        return;
    }
    
    // Proceed with bulk delete...
}
```

### 7. Loading States

Use wire:loading for better UX:

```blade
<flux:button type="submit" wire:loading.attr="disabled" wire:target="saveCategory">
    <span wire:loading.remove wire:target="saveCategory">
        {{ __('categories.form.save') }}
    </span>
    <span wire:loading wire:target="saveCategory">
        {{ __('admin.saving') }}
    </span>
</flux:button>
```

### 8. Accessibility

Always include ARIA labels:

```blade
<input
    type="search"
    wire:model.live.debounce.300ms="search"
    placeholder="{{ __('admin.search.placeholder') }}"
    aria-label="{{ __('admin.search.label') }}"
/>

<button
    type="button"
    wire:click="startEditing({{ $category->id }}, 'name')"
    aria-label="{{ __('admin.inline.edit_field', ['field' => __('admin.categories.table.name')]) }}"
>
    {{ $category->name }}
</button>
```

## Related Documentation

- [Admin Configuration Guide](ADMIN_CONFIGURATION.md)
- [Admin Config Quick Reference](ADMIN_CONFIG_QUICK_REFERENCE.md)
- [Optimistic UI Implementation](OPTIMISTIC_UI.md)
- [Property Testing Guide](../tests/PROPERTY_TESTING.md)

## Requirements Validation

This documentation supports all requirements in the admin Livewire CRUD specification:

- **Requirements 1.1-1.7**: Posts management
- **Requirements 2.1-2.7**: Categories management
- **Requirements 3.1-3.6**: Comments management
- **Requirements 4.1-4.6**: Users management
- **Requirements 5.1-5.5**: Inline editing
- **Requirements 6.1-6.5**: Modal workflows
- **Requirements 7.1-7.5**: Search and filtering
- **Requirements 8.1-8.5**: Bulk actions
- **Requirements 9.1-9.5**: Sortable columns
- **Requirements 10.1-10.5**: Validation
- **Requirements 11.1-11.5**: Relationship management
- **Requirements 12.1-12.5**: Optimistic UI updates
