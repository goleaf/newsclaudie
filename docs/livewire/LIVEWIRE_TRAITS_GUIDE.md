# Livewire Traits Documentation

Comprehensive documentation for all shared Livewire traits used in the admin interface.

## Table of Contents

- [Overview](#overview)
- [ManagesPerPage](#managesperpag)
- [ManagesBulkActions](#managesbulkactions)
- [ManagesSearch](#managessearch)
- [ManagesSorting](#managessorting)
- [Usage Examples](#usage-examples)
- [Best Practices](#best-practices)

## Overview

The admin interface uses shared traits to provide consistent functionality across all CRUD components. These traits handle common patterns like pagination, search, sorting, and bulk actions.

### Available Traits

| Trait | Purpose | Requirements |
|-------|---------|--------------|
| `ManagesPerPage` | Configurable pagination | All index views |
| `ManagesBulkActions` | Bulk selection and operations | Posts, Comments |
| `ManagesSearch` | Real-time search with debouncing | All index views |
| `ManagesSorting` | Sortable table columns | All index views |

## ManagesPerPage

**Location**: `app/Livewire/Concerns/ManagesPerPage.php`

**Purpose**: Provides configurable per-page pagination with query string persistence.

### Properties

```php
/**
 * Number of items to display per page.
 * Automatically sanitized against available options.
 */
public ?int $perPage = null;
```

### Methods

#### `bootManagesPerPage()`

Initializes the perPage property with the default value.

```php
public function bootManagesPerPage(): void
```

**Called**: Automatically by Livewire during component boot.

#### `updatingPerPage()`

Resets pagination to page 1 when per-page value changes.

```php
public function updatingPerPage(): void
```

**Called**: Automatically when perPage property is being updated.

#### `updatedPerPage($value)`

Sanitizes the per-page value to ensure it's within allowed options.

```php
public function updatedPerPage($value): void
```

**Parameters**:
- `$value` (mixed): The new per-page value

**Called**: Automatically after perPage property is updated.

#### `getPerPageOptionsProperty()`

Returns available per-page options for the dropdown.

```php
public function getPerPageOptionsProperty(): array
```

**Returns**: Array of integers representing available per-page options.

**Usage**: Access via `$this->perPageOptions` in Blade templates.

### Protected Methods

#### `defaultPerPage()`

Override this method to customize the default per-page value.

```php
protected function defaultPerPage(): int
```

**Returns**: Default number of items per page.

**Default**: Uses value from `config/interface.php` for the context.

#### `availablePerPageOptions()`

Override this method to customize available per-page options.

```php
protected function availablePerPageOptions(): array
```

**Returns**: Array of available per-page options.

**Default**: Uses options from `config/interface.php` for the context.

#### `perPageContext()`

Override this method to specify which config context to use.

```php
protected function perPageContext(): string
```

**Returns**: Context name (e.g., 'admin', 'comments', 'posts').

**Default**: Returns 'admin'.

### Usage Example

```php
use App\Livewire\Concerns\ManagesPerPage;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
    use ManagesPerPage;
    
    // Optional: Override default context
    protected function perPageContext(): string
    {
        return 'admin'; // Uses config('interface.pagination.defaults.admin')
    }
    
    public function with(): array
    {
        return [
            'items' => Model::query()
                ->paginate($this->perPage)
                ->withQueryString(),
        ];
    }
};
```

**Blade Template**:

```blade
<select wire:model.live="perPage">
    @foreach ($this->perPageOptions as $option)
        <option value="{{ $option }}">{{ $option }}</option>
    @endforeach
</select>
```

**Validation**: Supports all pagination requirements.

## ManagesBulkActions

**Location**: `app/Livewire/Concerns/ManagesBulkActions.php`

**Purpose**: Provides bulk selection tracking and operations with query string persistence.

### Properties

```php
/**
 * Array of selected item IDs.
 * Persisted in URL query string.
 *
 * @var array<int>
 */
#[Url(except: [], as: 'selected')]
public array $selected = [];

/**
 * Whether "select all" is active for the current page.
 * When true, all visible items on current page are selected.
 */
public bool $selectAll = false;

/**
 * IDs currently visible on the page.
 * Used to determine select all behavior.
 *
 * @var array<int>
 */
protected array $currentPageIds = [];
```

### Methods

#### `updatedSelectAll(bool $value)`

Called when selectAll checkbox is toggled.

```php
public function updatedSelectAll(bool $value): void
```

**Parameters**:
- `$value` (bool): New selectAll state

**Behavior**:
- If true: Selects all items on current page
- If false: Deselects all items on current page

#### `toggleSelection(int $id)`

Toggle selection state for a specific item.

```php
public function toggleSelection(int $id): void
```

**Parameters**:
- `$id` (int): The item ID to toggle

**Behavior**:
- If selected: Removes from selection
- If not selected: Adds to selection
- Updates selectAll state accordingly

#### `clearSelection()`

Clear all selections and reset selectAll state.

```php
public function clearSelection(): void
```

**Usage**: Call after bulk operations complete.

#### `getSelectedCountProperty()`

Get the count of selected items.

```php
public function getSelectedCountProperty(): int
```

**Returns**: Number of selected items.

**Usage**: Access via `$this->selectedCount` in component and Blade.

#### `setCurrentPageIds(iterable $ids)`

Set the IDs visible on the current page.

```php
public function setCurrentPageIds(iterable $ids): void
```

**Parameters**:
- `$ids` (iterable): Collection or array of IDs on current page

**Usage**: Call in `with()` method after fetching paginated data.

**Example**:
```php
public function with(): array
{
    $items = Model::query()->paginate($this->perPage);
    
    // Set current page IDs for select all functionality
    $this->setCurrentPageIds($items->pluck('id'));
    
    return ['items' => $items];
}
```

#### `getSelectedIds()`

Get normalized array of selected IDs.

```php
public function getSelectedIds(): array
```

**Returns**: Array of selected IDs as integers.

**Usage**: Use in bulk operations to get clean ID list.

### Protected Methods

#### `selectCurrentPage()`

Select all items on the current page.

```php
protected function selectCurrentPage(): void
```

**Called**: Automatically by `updatedSelectAll()`.

#### `deselectCurrentPage()`

Deselect all items on the current page.

```php
protected function deselectCurrentPage(): void
```

**Called**: Automatically by `updatedSelectAll()`.

#### `updateSelectAllState()`

Update selectAll checkbox state based on current selections.

```php
protected function updateSelectAllState(): void
```

**Behavior**: Sets selectAll to true if all items on current page are selected.

#### `normalizeSelection(array $ids)`

Normalize selection array to ensure all values are integers.

```php
protected function normalizeSelection(array $ids): array
```

**Parameters**:
- `$ids` (array): Array of IDs to normalize

**Returns**: Array of unique integer IDs.

### Usage Example

```php
use App\Livewire\Concerns\ManagesBulkActions;

new class extends Component {
    use ManagesBulkActions;
    
    public function bulkDelete(): void
    {
        // Validate selection count
        if (AdminConfig::exceedsBulkActionLimit($this->selectedCount)) {
            $this->addError('selected', 'Too many items selected');
            return;
        }
        
        // Get normalized IDs
        $ids = $this->getSelectedIds();
        
        // Perform bulk operation
        Model::query()->whereIn('id', $ids)->delete();
        
        // Clear selection
        $this->clearSelection();
        
        // Show success message
        session()->flash('success', "Deleted {$this->selectedCount} items");
    }
    
    public function with(): array
    {
        $items = Model::query()->paginate($this->perPage);
        
        // Required: Set current page IDs
        $this->setCurrentPageIds($items->pluck('id'));
        
        return ['items' => $items];
    }
};
```

**Blade Template**:

```blade
<!-- Select all checkbox -->
<input
    type="checkbox"
    wire:model.live="selectAll"
    aria-label="Select all on page"
/>

<!-- Individual item checkboxes -->
@foreach ($items as $item)
    <input
        type="checkbox"
        wire:model.live="selected"
        value="{{ $item->id }}"
        aria-label="Select {{ $item->name }}"
    />
@endforeach

<!-- Bulk actions toolbar -->
@if ($this->selectedCount > 0)
    <div>
        <span>{{ $this->selectedCount }} selected</span>
        <button wire:click="bulkDelete">Delete</button>
        <button wire:click="clearSelection">Clear</button>
    </div>
@endif
```

**Validation**: Requirements 8.1, 8.2, 8.3, 8.4

## ManagesSearch

**Location**: `app/Livewire/Concerns/ManagesSearch.php`

**Purpose**: Provides real-time search with debouncing and query string persistence.

### Properties

```php
/**
 * The current search term.
 * Persisted in URL query string as 'search' parameter.
 */
#[Url(except: '', as: 'search')]
public ?string $search = null;
```

### Methods

#### `updatingSearch()`

Called before search value changes. Resets pagination to page 1.

```php
public function updatingSearch(): void
```

**Called**: Automatically when search property is being updated.

#### `updatedSearch(?string $value)`

Called after search value changes. Normalizes the search term.

```php
public function updatedSearch(?string $value): void
```

**Parameters**:
- `$value` (string|null): New search value

**Behavior**: Trims whitespace from search term.

#### `clearSearch()`

Clear the search term and reset to default view.

```php
public function clearSearch(): void
```

**Usage**: Call from "clear search" button.

### Protected Methods

#### `applySearch(Builder $query, array $searchableFields)`

Apply search filtering to a query builder.

```php
protected function applySearch(Builder $query, array $searchableFields): Builder
```

**Parameters**:
- `$query` (Builder): The query builder to apply search to
- `$searchableFields` (array): Array of field names to search in

**Returns**: Modified query builder with search applied.

**Example**:
```php
$query = Model::query();
$query = $this->applySearch($query, ['name', 'email', 'description']);
```

#### `getSearchTerm()`

Get the normalized search term.

```php
protected function getSearchTerm(): string
```

**Returns**: Trimmed search term.

#### `normalizeSearch(?string $value)`

Normalize a search value by trimming whitespace.

```php
protected function normalizeSearch(?string $value): string
```

**Parameters**:
- `$value` (string|null): Value to normalize

**Returns**: Trimmed string.

### Usage Example

```php
use App\Livewire\Concerns\ManagesSearch;

new class extends Component {
    use ManagesSearch;
    
    public function with(): array
    {
        $query = Model::query();
        
        // Apply search across multiple fields
        $query = $this->applySearch($query, ['name', 'email', 'description']);
        
        return [
            'items' => $query->paginate($this->perPage),
            'searchTerm' => $this->getSearchTerm(),
        ];
    }
};
```

**Blade Template**:

```blade
<input
    type="search"
    wire:model.live.debounce.{{ \App\Support\AdminConfig::searchDebounceMs() }}ms="search"
    placeholder="Search..."
    aria-label="Search"
/>

@if ($searchTerm !== '')
    <button wire:click="clearSearch">Clear</button>
    <span>Searching for: {{ $searchTerm }}</span>
@endif
```

**Validation**: Requirements 7.1, 7.4

## ManagesSorting

**Location**: `app/Livewire/Concerns/ManagesSorting.php`

**Purpose**: Provides sortable column logic with direction toggle and query string persistence.

### Properties

```php
/**
 * The field to sort by.
 * Persisted in URL query string as 'sort' parameter.
 */
#[Url(except: null, as: 'sort')]
public ?string $sortField = null;

/**
 * The sort direction ('asc' or 'desc').
 * Persisted in URL query string as 'direction' parameter.
 */
#[Url(except: 'asc', as: 'direction')]
public string $sortDirection = 'asc';
```

### Methods

#### `sortBy(string $field)`

Sort by a specific field. Toggles direction if already sorting by this field.

```php
public function sortBy(string $field): void
```

**Parameters**:
- `$field` (string): The field name to sort by

**Behavior**:
- If already sorting by this field: Toggles direction (asc ↔ desc)
- If new field: Sets field and defaults to ascending

#### `clearSort()`

Clear the sort state and return to default.

```php
public function clearSort(): void
```

**Usage**: Call from "clear sort" button.

### Protected Methods

#### `applySorting(Builder $query, array $sortableFields = [])`

Apply sorting to a query builder.

```php
protected function applySorting(Builder $query, array $sortableFields = []): Builder
```

**Parameters**:
- `$query` (Builder): The query builder to apply sorting to
- `$sortableFields` (array): Optional list of allowed sortable fields

**Returns**: Modified query builder with sorting applied.

**Validation**: If sortableFields provided, only allows sorting by those fields.

**Example**:
```php
$query = Model::query();
$query = $this->applySorting($query, ['name', 'created_at', 'posts_count']);
```

#### `isSortedBy(string $field)`

Check if currently sorting by a specific field.

```php
public function isSortedBy(string $field): bool
```

**Parameters**:
- `$field` (string): Field name to check

**Returns**: True if currently sorting by this field.

#### `getSortDirection(string $field)`

Get the current sort direction for a field.

```php
public function getSortDirection(string $field): ?string
```

**Parameters**:
- `$field` (string): Field name to check

**Returns**: 'asc', 'desc', or null if not sorting by this field.

#### `normalizeSortDirection(string $direction)`

Normalize sort direction to ensure it's either 'asc' or 'desc'.

```php
protected function normalizeSortDirection(string $direction): string
```

**Parameters**:
- `$direction` (string): Direction to normalize

**Returns**: 'asc' or 'desc' (defaults to 'asc' if invalid).

### Usage Example

```php
use App\Livewire\Concerns\ManagesSorting;

new class extends Component {
    use ManagesSorting;
    
    protected function sortableFields(): array
    {
        return ['name', 'created_at', 'posts_count'];
    }
    
    public function with(): array
    {
        $query = Model::query();
        
        // Apply sorting with validation
        $query = $this->applySorting($query, $this->sortableFields());
        
        return ['items' => $query->paginate($this->perPage)];
    }
};
```

**Blade Template**:

```blade
<!-- Sortable column header -->
<th>
    <button
        wire:click="sortBy('name')"
        class="{{ $this->isSortedBy('name') ? 'font-bold' : '' }}"
    >
        Name
        @if ($this->isSortedBy('name'))
            @if ($this->getSortDirection('name') === 'asc')
                ↑
            @else
                ↓
            @endif
        @endif
    </button>
</th>

<!-- Sort direction toggle -->
<button wire:click="sortBy('{{ $sortField }}')">
    {{ $sortDirection === 'asc' ? 'Ascending' : 'Descending' }}
</button>
```

**Validation**: Requirements 9.1, 9.2, 9.4, 9.5

## Usage Examples

### Complete Component with All Traits

```php
<?php

use App\Livewire\Concerns\ManagesBulkActions;
use App\Livewire\Concerns\ManagesPerPage;
use App\Livewire\Concerns\ManagesSearch;
use App\Livewire\Concerns\ManagesSorting;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
    use ManagesPerPage;
    use ManagesBulkActions;
    use ManagesSearch;
    use ManagesSorting;
    
    protected function sortableFields(): array
    {
        return ['name', 'created_at', 'posts_count'];
    }
    
    public function bulkDelete(): void
    {
        $ids = $this->getSelectedIds();
        Model::query()->whereIn('id', $ids)->delete();
        $this->clearSelection();
    }
    
    public function with(): array
    {
        $query = Model::query();
        
        // Apply search
        $query = $this->applySearch($query, ['name', 'description']);
        
        // Apply sorting
        $query = $this->applySorting($query, $this->sortableFields());
        
        // Paginate
        $items = $query->paginate($this->perPage)->withQueryString();
        
        // Set current page IDs for bulk actions
        $this->setCurrentPageIds($items->pluck('id'));
        
        return [
            'items' => $items,
            'searchTerm' => $this->getSearchTerm(),
        ];
    }
};
```

## Best Practices

### 1. Always Call setCurrentPageIds

When using `ManagesBulkActions`, always call `setCurrentPageIds()` in your `with()` method:

```php
public function with(): array
{
    $items = Model::query()->paginate($this->perPage);
    
    // Required for select all functionality
    $this->setCurrentPageIds($items->pluck('id'));
    
    return ['items' => $items];
}
```

### 2. Validate Sortable Fields

Always provide a list of sortable fields to prevent SQL injection:

```php
protected function sortableFields(): array
{
    return ['name', 'created_at', 'posts_count'];
}

public function with(): array
{
    $query = Model::query();
    
    // Validates against sortableFields()
    $query = $this->applySorting($query, $this->sortableFields());
    
    return ['items' => $query->paginate($this->perPage)];
}
```

### 3. Use AdminConfig for Debounce Timing

Always use `AdminConfig` helper for consistent debounce timing:

```blade
<input
    type="search"
    wire:model.live.debounce.{{ \App\Support\AdminConfig::searchDebounceMs() }}ms="search"
    placeholder="Search..."
/>
```

### 4. Reset Pagination on Filter Changes

The traits automatically reset pagination when search changes, but you should also reset for other filters:

```php
public function updatedStatusFilter(): void
{
    $this->resetPage();
}

public function updatedCategoryFilter(): void
{
    $this->resetPage();
}
```

### 5. Validate Bulk Action Limits

Always validate bulk action selection counts:

```php
use App\Support\AdminConfig;

public function bulkDelete(): void
{
    if (AdminConfig::exceedsBulkActionLimit($this->selectedCount)) {
        $this->addError('selected', 'Selection exceeds maximum limit');
        return;
    }
    
    // Proceed with bulk operation...
}
```

### 6. Clear Selection After Bulk Operations

Always clear selection after completing bulk operations:

```php
public function bulkDelete(): void
{
    $ids = $this->getSelectedIds();
    Model::query()->whereIn('id', $ids)->delete();
    
    // Clear selection
    $this->clearSelection();
    
    // Show success message
    session()->flash('success', "Deleted {$this->selectedCount} items");
}
```

### 7. Use withQueryString() for Pagination

Always use `withQueryString()` to preserve filters in pagination links:

```php
public function with(): array
{
    return [
        'items' => Model::query()
            ->paginate($this->perPage)
            ->withQueryString(), // Preserves search, sort, filters
    ];
}
```

## Related Documentation

- [Admin Configuration Guide](../admin/ADMIN_CONFIGURATION.md)
- [Volt Component Guide](../volt/VOLT_COMPONENT_GUIDE.md)
- [Admin Config Quick Reference](../admin/ADMIN_CONFIG_QUICK_REFERENCE.md)

## Requirements Validation

These traits support the following requirements:

- **ManagesPerPage**: All pagination requirements
- **ManagesBulkActions**: Requirements 8.1, 8.2, 8.3, 8.4
- **ManagesSearch**: Requirements 7.1, 7.4
- **ManagesSorting**: Requirements 9.1, 9.2, 9.4, 9.5
