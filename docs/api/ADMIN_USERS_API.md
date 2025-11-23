# Admin Users API Reference

Complete API reference for the Admin Users Management component.

## Component Class

**Namespace:** `Livewire\Volt\Component`  
**Location:** `resources/views/livewire/admin/users/index.blade.php`  
**Route:** `/admin/users`

## Properties

### Public Properties

#### `$page`
```php
#[Url(except: 1)]
public int $page = 1;
```
Current pagination page number. Persisted in URL query string.

**Type:** `int`  
**Default:** `1`  
**URL Parameter:** `page`

---

#### `$showCreateModal`
```php
public bool $showCreateModal = false;
```
Controls visibility of the create user modal.

**Type:** `bool`  
**Default:** `false`

---

#### `$showDeleteModal`
```php
public bool $showDeleteModal = false;
```
Controls visibility of the delete confirmation modal.

**Type:** `bool`  
**Default:** `false`

---

#### `$createForm`
```php
public array $createForm = [
    'name' => '',
    'email' => '',
    'password' => '',
    'password_confirmation' => '',
    'is_admin' => false,
    'is_author' => false,
    'is_banned' => false,
];
```
Form data for creating new users.

**Type:** `array`  
**Structure:**
- `name` (string): User's full name
- `email` (string): User's email address
- `password` (string): User's password
- `password_confirmation` (string): Password confirmation
- `is_admin` (bool): Admin role flag
- `is_author` (bool): Author role flag
- `is_banned` (bool): Banned status flag

---

#### `$deletingUserId`
```php
public ?int $deletingUserId = null;
```
ID of the user being deleted.

**Type:** `int|null`  
**Default:** `null`

---

#### `$deleteContext`
```php
public array $deleteContext = [
    'name' => '',
    'posts' => 0,
    'comments' => 0,
];
```
Context information for delete operation.

**Type:** `array`  
**Structure:**
- `name` (string): User's name
- `posts` (int): Number of posts
- `comments` (int): Number of comments

---

#### `$deleteStrategy`
```php
public string $deleteStrategy = 'transfer';
```
Strategy for handling user's content on deletion.

**Type:** `string`  
**Default:** `'transfer'`  
**Values:** `'transfer'` | `'delete'`

---

#### `$transferTarget`
```php
public ?int $transferTarget = null;
```
Target user ID for content transfer.

**Type:** `int|null`  
**Default:** `null`

---

#### `$transferOptions`
```php
public array $transferOptions = [];
```
Available users for content transfer.

**Type:** `array`  
**Structure:** Array of `['id' => int, 'name' => string]`

---

### Trait Properties

#### From `ManagesPerPage`

##### `$perPage`
```php
public ?int $perPage = null;
```
Number of items per page.

**Type:** `int|null`  
**Default:** `20` (from config)  
**URL Parameter:** `perPage`

---

#### From `ManagesSearch`

##### `$search`
```php
#[Url(except: '', as: 'search')]
public ?string $search = null;
```
Current search term.

**Type:** `string|null`  
**Default:** `null`  
**URL Parameter:** `search`

---

#### From `ManagesSorting`

##### `$sortField`
```php
#[Url(except: null, as: 'sort')]
public ?string $sortField = null;
```
Field to sort by.

**Type:** `string|null`  
**Default:** `'name'`  
**URL Parameter:** `sort`  
**Allowed Values:** `'name'`, `'email'`, `'created_at'`, `'posts_count'`

##### `$sortDirection`
```php
#[Url(except: 'asc', as: 'direction')]
public string $sortDirection = 'asc';
```
Sort direction.

**Type:** `string`  
**Default:** `'asc'`  
**URL Parameter:** `direction`  
**Allowed Values:** `'asc'`, `'desc'`

---

### Protected Properties

#### `$listeners`
```php
protected $listeners = ['user-updated' => '$refresh'];
```
Livewire event listeners.

---

#### `$queryString`
```php
protected $queryString = [
    'perPage' => ['except' => 20],
    'search' => ['except' => ''],
    'sortField' => ['as' => 'sort', 'except' => 'name'],
    'sortDirection' => ['as' => 'direction', 'except' => 'asc'],
];
```
Query string configuration for URL persistence.

---

## Methods

### Lifecycle Methods

#### `mount()`
```php
public function mount(int $page = 1): void
```
Initialize component state.

**Parameters:**
- `$page` (int): Initial page number (default: 1)

**Process:**
1. Resolve page from query string or parameter
2. Set page to minimum of 1
3. Resolve sort field and direction

**Example:**
```php
// Component mounted at /admin/users?page=2
mount(2); // Sets $this->page = 2
```

---

#### `with()`
```php
public function with(): array
```
Provide data to the view.

**Returns:** `array`
- `users` (LengthAwarePaginator): Paginated users
- `searchTerm` (string): Current search term
- `isFiltered` (bool): Whether filters are active

**Process:**
1. Build query with eager loading
2. Apply search filter
3. Apply sorting
4. Add secondary sort
5. Paginate results

**Example:**
```php
$data = $this->with();
// [
//     'users' => LengthAwarePaginator,
//     'searchTerm' => 'john',
//     'isFiltered' => true,
// ]
```

---

### Modal Methods

#### `openCreateModal()`
```php
public function openCreateModal(): void
```
Open the create user modal.

**Process:**
1. Reset create form
2. Clear error bag
3. Clear validation
4. Set `$showCreateModal = true`

**Example:**
```php
// User clicks "Create User" button
$this->openCreateModal();
// Modal opens with empty form
```

---

### CRUD Methods

#### `createUser()`
```php
public function createUser(): void
```
Create a new user.

**Authorization:** Admin only (`UserPolicy::create()`)

**Process:**
1. Authorize action
2. Validate form data
3. Hash password
4. Create user record
5. Flash success message
6. Reset form and close modal
7. Reset pagination
8. Dispatch refresh event

**Validation Rules:**
```php
[
    'createForm.name' => ['required', 'string', 'max:255'],
    'createForm.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
    'createForm.password' => ['required', 'string', 'min:8', 'confirmed'],
    'createForm.is_admin' => ['boolean'],
    'createForm.is_author' => ['boolean'],
    'createForm.is_banned' => ['boolean'],
]
```

**Throws:** `AuthorizationException` if not authorized

**Example:**
```php
$this->createForm = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'is_admin' => false,
    'is_author' => true,
    'is_banned' => false,
];

$this->createUser();
// Creates user and shows success message
```

---

#### `confirmDelete()`
```php
public function confirmDelete(User $user): void
```
Confirm user deletion.

**Parameters:**
- `$user` (User): User to delete

**Authorization:** Admin only (`UserPolicy::delete()`)

**Process:**
1. Authorize action
2. Set `$deletingUserId`
3. Set default delete strategy
4. Load delete context (posts, comments)
5. Load transfer options
6. Open delete modal

**Example:**
```php
$user = User::find(5);
$this->confirmDelete($user);
// Opens delete modal with user context
```

---

#### `cancelDelete()`
```php
public function cancelDelete(): void
```
Cancel delete operation.

**Process:**
1. Close delete modal
2. Reset delete state
3. Clear delete context
4. Clear transfer options

---

#### `deleteUser()`
```php
public function deleteUser(): void
```
Execute user deletion.

**Authorization:** Admin only (`UserPolicy::delete()`)

**Process:**
1. Find user by ID
2. Authorize action
3. Resolve delete strategy
4. Execute strategy (transfer or delete)
5. Delete comments by user
6. Delete user record
7. Flash success message
8. Cancel delete and refresh

**Delete Strategies:**

**Transfer:**
```php
// Transfer posts to target user
Post::where('user_id', $user->id)
    ->update(['user_id' => $target->id]);
```

**Delete:**
```php
// Delete all posts and their comments
$postIds = Post::where('user_id', $user->id)->pluck('id');
Comment::whereIn('post_id', $postIds)->delete();
Post::whereIn('id', $postIds)->delete();
```

**Example:**
```php
// Transfer strategy
$this->deleteStrategy = 'transfer';
$this->transferTarget = 1;
$this->deleteUser();
// Transfers posts to user #1, deletes user

// Delete strategy
$this->deleteStrategy = 'delete';
$this->deleteUser();
// Deletes all posts and comments, deletes user
```

---

### Role Management Methods

#### `toggleAdmin()`
```php
public function toggleAdmin(User $user): void
```
Toggle admin role for user.

**Parameters:**
- `$user` (User): User to modify

**Authorization:** Admin only (`UserPolicy::update()`)

**Process:**
1. Authorize action
2. Check if modifying self (prevent)
3. Toggle `is_admin` flag
4. Save user
5. Flash success message
6. Dispatch refresh event

**Example:**
```php
$user = User::find(5);
$this->toggleAdmin($user);
// Toggles admin role and shows message
```

---

#### `toggleAuthor()`
```php
public function toggleAuthor(User $user): void
```
Toggle author role for user.

**Parameters:**
- `$user` (User): User to modify

**Authorization:** Admin only (`UserPolicy::update()`)

**Process:**
1. Authorize action
2. Check if modifying self (prevent)
3. Toggle `is_author` flag
4. Save user
5. Flash success message
6. Dispatch refresh event

**Example:**
```php
$user = User::find(5);
$this->toggleAuthor($user);
// Toggles author role and shows message
```

---

#### `toggleBan()`
```php
public function toggleBan(User $user): void
```
Toggle ban status for user.

**Parameters:**
- `$user` (User): User to modify

**Authorization:** Admin only (`UserPolicy::ban()`)

**Process:**
1. Check if modifying self (prevent)
2. Authorize action
3. Toggle `is_banned` flag
4. Save user
5. Flash success message
6. Dispatch refresh event

**Example:**
```php
$user = User::find(5);
$this->toggleBan($user);
// Toggles ban status and shows message
```

---

### Search Methods

#### `applySearchShortcut()`
```php
public function applySearchShortcut(): void
```
Apply search when Enter key is pressed.

**Process:**
1. Reset pagination
2. Sanitize search term

---

#### `clearSearch()`
```php
public function clearSearch(): void
```
Clear search term.

**Process:**
1. Reset pagination
2. Clear search term

---

#### `clearFilters()`
```php
public function clearFilters(): void
```
Clear all filters.

**Process:**
1. Call `clearSearch()`

---

### Validation Methods

#### `updatedCreateForm()`
```php
public function updatedCreateForm($value, string $key): void
```
Validate create form field on update.

**Parameters:**
- `$value` (mixed): New field value
- `$key` (string): Field key

**Process:**
1. Build property path
2. Validate only that field
3. Show errors immediately

**Example:**
```php
// User types in email field
updatedCreateForm('john@example.com', 'email');
// Validates email field only
```

---

### Protected Methods

#### `defaultSortField()`
```php
protected function defaultSortField(): string
```
Get default sort field.

**Returns:** `string` - `'name'`

---

#### `defaultSortDirection()`
```php
protected function defaultSortDirection(): string
```
Get default sort direction.

**Returns:** `string` - `'asc'`

---

#### `sortableColumns()`
```php
protected function sortableColumns(): array
```
Get sortable columns.

**Returns:** `array` - `['name', 'email', 'created_at', 'posts_count']`

---

#### `resolvedSort()`
```php
protected function resolvedSort(): array
```
Resolve sort field and direction.

**Returns:** `array` - `[$field, $direction]`

**Process:**
1. Get field from property or default
2. Get direction from property or default
3. Validate field is sortable
4. Return tuple

---

#### `resolveDeleteStrategy()`
```php
protected function resolveDeleteStrategy(?string $strategy): string
```
Resolve delete strategy.

**Parameters:**
- `$strategy` (string|null): Strategy to resolve

**Returns:** `string` - `'transfer'` or `'delete'`

**Process:**
1. Check if strategy is valid
2. Return strategy or default to `'transfer'`

---

#### `createRules()`
```php
protected function createRules(): array
```
Get validation rules for create form.

**Returns:** `array` - Validation rules

---

#### `resetCreateForm()`
```php
protected function resetCreateForm(): void
```
Reset create form to default values.

---

## Events

### Dispatched Events

#### `user-updated`
```php
$this->dispatch('user-updated');
```
Dispatched when a user is created, updated, or deleted.

**Listeners:**
- Component itself (refreshes data)

**Example:**
```php
// In another component
protected $listeners = ['user-updated' => 'handleUserUpdate'];

public function handleUserUpdate(): void
{
    // Refresh data or perform action
}
```

---

## Query String Parameters

### URL Structure

```
/admin/users?page=2&perPage=50&search=john&sort=email&direction=desc
```

### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Current page number |
| `perPage` | int | 20 | Items per page |
| `search` | string | '' | Search term |
| `sort` | string | 'name' | Sort field |
| `direction` | string | 'asc' | Sort direction |

### Examples

**Default view:**
```
/admin/users
```

**Search for "john":**
```
/admin/users?search=john
```

**Sort by email descending:**
```
/admin/users?sort=email&direction=desc
```

**Page 2 with 50 items:**
```
/admin/users?page=2&perPage=50
```

**Combined:**
```
/admin/users?search=john&sort=email&direction=desc&page=2&perPage=50
```

---

## Database Queries

### Main Query

```php
User::query()
    ->withCount([
        'posts as posts_count' => fn ($query) => $query->withoutGlobalScopes(),
        'comments',
    ])
    ->when($search !== '', function ($query) use ($search) {
        $query->where(function ($inner) use ($search) {
            $inner->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%');
        });
    })
    ->orderBy($sortField, $sortDirection)
    ->when($sortField !== 'name', fn ($q) => $q->orderBy('name'))
    ->paginate($this->perPage)
    ->withQueryString();
```

### Eager Loading

```php
->withCount([
    'posts as posts_count' => fn ($query) => $query->withoutGlobalScopes(),
    'comments',
])
```

**Prevents N+1 queries** when displaying post and comment counts.

---

## Authorization

All methods check authorization using `UserPolicy`:

```php
$this->authorize('create', User::class);
$this->authorize('update', $user);
$this->authorize('delete', $user);
$this->authorize('ban', $user);
```

**Policy Methods:**
- `create()` - Admin only
- `update()` - Admin only
- `delete()` - Admin only (with restrictions)
- `ban()` - Admin only (with restrictions)

---

## Error Handling

### Authorization Errors

```php
try {
    $this->authorize('delete', $user);
} catch (AuthorizationException $exception) {
    session()->flash('error', $exception->getMessage());
    return;
}
```

### Validation Errors

```php
$this->validate($this->createRules());
// Throws ValidationException if validation fails
// Livewire handles displaying errors automatically
```

---

## Flash Messages

### Success Messages

```php
session()->flash('status', __('admin.users.created', ['name' => $user->name]));
```

### Error Messages

```php
session()->flash('error', $exception->getMessage());
```

### Display in View

```blade
@if (session('status'))
    <flux:callout color="green">
        {{ session('status') }}
    </flux:callout>
@endif

@if (session('error'))
    <flux:callout color="red">
        {{ session('error') }}
    </flux:callout>
@endif
```

---

## Performance Considerations

### Eager Loading

Always eager load relationships to prevent N+1 queries:

```php
->withCount(['posts', 'comments'])
```

### Query Optimization

- Indexes on `name`, `email`, `created_at`
- Secondary sort for consistency
- Pagination to limit results

### Debouncing

Search input debounced to 400ms to reduce server requests:

```blade
wire:model.live.debounce.400ms="search"
```

---

## Security

### Authorization

All operations protected by policies:
- Create: Admin only
- Update: Admin only
- Delete: Admin only (cannot delete self or other admins)
- Ban: Admin only (cannot ban self or other admins)

### Password Hashing

Passwords automatically hashed using Laravel's `Hash` facade:

```php
'password' => Hash::make($validated['createForm']['password'])
```

### Mass Assignment Protection

User model has `$fillable` property to prevent mass assignment vulnerabilities.

### CSRF Protection

All forms protected by Laravel's CSRF middleware (automatic with Livewire).

---

## Testing

### Unit Tests

```php
test('creates user with valid data', function () {
    $admin = User::factory()->admin()->create();
    
    Livewire::actingAs($admin)
        ->test(UsersIndex::class)
        ->set('createForm.name', 'John Doe')
        ->set('createForm.email', 'john@example.com')
        ->set('createForm.password', 'password123')
        ->set('createForm.password_confirmation', 'password123')
        ->call('createUser')
        ->assertHasNoErrors();
    
    expect(User::where('email', 'john@example.com')->exists())->toBeTrue();
});
```

### Feature Tests

```php
test('admin can toggle user roles', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_author' => false]);
    
    Livewire::actingAs($admin)
        ->test(UsersIndex::class)
        ->call('toggleAuthor', $user->id)
        ->assertHasNoErrors();
    
    expect($user->fresh()->is_author)->toBeTrue();
});
```

---

## Related Models

### User Model

**Location:** `app/Models/User.php`

**Relationships:**
- `posts()` - HasMany
- `publishedPosts()` - HasMany (filtered)
- `comments()` - HasMany
- `approvedComments()` - HasMany (filtered)
- `dataExports()` - HasMany

**Scopes:**
- `admins()` - Filter admin users
- `authors()` - Filter author users
- `active()` - Filter non-banned users
- `banned()` - Filter banned users

**Methods:**
- `isAdmin()` - Check if admin
- `isAuthor()` - Check if author
- `isBanned()` - Check if banned
- `canPublish()` - Check if can publish posts

---

## Related Policies

### UserPolicy

**Location:** `app/Policies/UserPolicy.php`

**Methods:**
- `viewAny()` - All users
- `view()` - All users
- `create()` - Admin only
- `update()` - Admin only
- `delete()` - Admin only (with restrictions)
- `ban()` - Admin only (with restrictions)

---

## Version History

- **v1.0.0** (2025-11-23) - Initial API documentation

---

**Last Updated:** 2025-11-23
**Component Version:** 1.0.0
