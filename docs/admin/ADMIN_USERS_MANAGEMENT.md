# Admin Users Management

Complete documentation for the admin users management interface.

## Overview

The Users Management component provides a comprehensive interface for managing user accounts, roles, and permissions in the admin panel. Built with Livewire Volt, it offers real-time search, sorting, role management, and user moderation capabilities.

**Component Location:** `resources/views/livewire/admin/users/index.blade.php`

## Table of Contents

- [Features](#features)
- [Architecture](#architecture)
- [User Interface](#user-interface)
- [User Roles](#user-roles)
- [Operations](#operations)
- [Authorization](#authorization)
- [Search and Filtering](#search-and-filtering)
- [Sorting](#sorting)
- [Validation](#validation)
- [Delete Strategies](#delete-strategies)
- [Keyboard Shortcuts](#keyboard-shortcuts)
- [Accessibility](#accessibility)
- [Configuration](#configuration)
- [Translation Keys](#translation-keys)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

## Features

### Core Functionality

- **User Listing**: Paginated table with search and sort capabilities
- **Create Users**: Modal-based user creation with role assignment
- **Role Management**: Toggle admin and author roles inline
- **Ban Management**: Ban/unban users with policy enforcement
- **User Deletion**: Delete users with content transfer options
- **Real-time Search**: Debounced search across name and email
- **Sortable Columns**: Sort by name, email, join date, or post count
- **URL Persistence**: Search, sort, and pagination state in URL
- **Loading States**: Visual feedback for all async operations
- **Validation**: Real-time validation with helpful error messages

### User Roles

The system supports three user roles:

1. **Admin**: Full system access, can manage all users and content
2. **Author**: Can create and publish posts
3. **Reader**: Can view content and post comments (default)

### User Status

- **Active**: Normal user account
- **Banned**: User cannot log in or interact with the system

## Architecture

### Component Structure

```php
<?php
use App\Livewire\Concerns\ManagesPerPage;
use App\Livewire\Concerns\ManagesSearch;
use App\Livewire\Concerns\ManagesSorting;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use AuthorizesRequests;
    use ManagesPerPage;
    use ManagesSearch;
    use ManagesSorting;
    use WithPagination;
    
    // Component implementation
};
```

### Traits Used

| Trait | Purpose | Documentation |
|-------|---------|---------------|
| `AuthorizesRequests` | Policy-based authorization | Laravel Docs |
| `ManagesPerPage` | Pagination configuration | [Livewire Traits Guide](LIVEWIRE_TRAITS_GUIDE.md#managesperpage) |
| `ManagesSearch` | Search with debouncing | [Livewire Traits Guide](LIVEWIRE_TRAITS_GUIDE.md#managessearch) |
| `ManagesSorting` | Sortable columns | [Livewire Traits Guide](LIVEWIRE_TRAITS_GUIDE.md#managessorting) |
| `WithPagination` | Livewire pagination | Livewire Docs |

### Data Flow

```
User Action → Livewire Method → Authorization Check → Database Operation → UI Update
     ↓              ↓                    ↓                    ↓              ↓
  Click Toggle  toggleAdmin()      UserPolicy::update()   User::save()   Refresh
```

## User Interface

### Main Table

The users table displays:

- **User Column**: Name and email
- **Roles Column**: Admin, Author, or Reader badges
- **Status Column**: Active or Banned badge
- **Content Column**: Post and comment counts
- **Joined Column**: Account creation date (human-readable)
- **Actions Column**: Role toggles and delete button

### Toolbar

- **Search Input**: Real-time search with ⌘K shortcut
- **Sort Dropdown**: Select sort field (name, email, joined, posts)
- **Sort Direction**: Toggle ascending/descending
- **Clear Filters**: Reset search and filters

### Create Modal

Modal form with fields:
- Name (required)
- Email (required, unique)
- Password (required, min 8 characters)
- Password Confirmation (required)
- Admin Role (checkbox)
- Author Role (checkbox)
- Banned Status (checkbox)

### Delete Modal

Confirmation modal with:
- User context (posts count, comments count)
- Delete strategy selection (transfer or delete)
- Transfer target selection (if transfer strategy)
- Warning message
- Confirm/Cancel actions

## User Roles

### Admin Role

**Capabilities:**
- Full access to admin panel
- Create, edit, delete all content
- Manage all users
- Assign/revoke admin and author roles
- Ban/unban users

**Restrictions:**
- Cannot modify own admin status
- Cannot delete own account
- Cannot delete other admins
- Cannot ban other admins

### Author Role

**Capabilities:**
- Create and edit own posts
- Publish posts
- Moderate comments on own posts

**Restrictions:**
- Cannot access user management
- Cannot manage other users' content

### Reader Role

**Capabilities:**
- View published content
- Post comments (if verified)

**Restrictions:**
- No admin panel access
- Cannot create posts

## Operations

### Create User

**Method:** `createUser()`

**Process:**
1. Validate form data
2. Check authorization (admin only)
3. Hash password
4. Create user record
5. Flash success message
6. Close modal and refresh list

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

**Example:**
```php
// User clicks "Create User" button
$this->openCreateModal();

// User fills form and submits
$this->createUser();
// → Validates input
// → Creates user
// → Shows success message
// → Refreshes list
```

### Toggle Admin Role

**Method:** `toggleAdmin(User $user)`

**Process:**
1. Check authorization (admin only)
2. Prevent self-modification
3. Toggle `is_admin` flag
4. Save user
5. Flash success message
6. Refresh UI

**Authorization:**
- Must be admin
- Cannot modify own account
- Policy: `UserPolicy::update()`

**Example:**
```php
// Admin clicks admin toggle for user #5
$this->toggleAdmin($user);
// → Checks if current user is admin
// → Checks if not modifying self
// → Toggles is_admin flag
// → Saves user
// → Shows "User is now an admin" message
```

### Toggle Author Role

**Method:** `toggleAuthor(User $user)`

**Process:**
1. Check authorization (admin only)
2. Prevent self-modification
3. Toggle `is_author` flag
4. Save user
5. Flash success message
6. Refresh UI

**Authorization:**
- Must be admin
- Cannot modify own account
- Policy: `UserPolicy::update()`

### Toggle Ban Status

**Method:** `toggleBan(User $user)`

**Process:**
1. Check authorization (admin only)
2. Prevent self-ban
3. Prevent banning admins
4. Toggle `is_banned` flag
5. Save user
6. Flash success message
7. Refresh UI

**Authorization:**
- Must be admin
- Cannot ban self
- Cannot ban other admins
- Policy: `UserPolicy::ban()`

**Example:**
```php
// Admin clicks ban toggle for user #5
$this->toggleBan($user);
// → Checks if current user is admin
// → Checks if not banning self
// → Checks if target is not admin
// → Toggles is_banned flag
// → Saves user
// → Shows "User has been banned" message
```

### Delete User

**Method:** `deleteUser()`

**Process:**
1. Find user by ID
2. Check authorization (admin only)
3. Prevent self-deletion
4. Prevent deleting admins
5. Execute delete strategy (transfer or delete)
6. Delete user record
7. Flash success message
8. Close modal and refresh list

**Delete Strategies:**

#### Transfer Strategy (Default)
- Transfer all posts to target user
- Delete all comments by user
- Delete user account

#### Delete Strategy
- Delete all posts by user
- Delete all comments on those posts
- Delete all comments by user
- Delete user account

**Authorization:**
- Must be admin
- Cannot delete self
- Cannot delete other admins
- Policy: `UserPolicy::delete()`

**Example:**
```php
// Admin clicks delete button for user #5
$this->confirmDelete($user);
// → Opens delete modal
// → Shows user context (posts, comments)
// → Presents strategy options

// Admin selects "Transfer" and target user
$this->deleteStrategy = 'transfer';
$this->transferTarget = 1; // Admin user

// Admin confirms deletion
$this->deleteUser();
// → Transfers posts to user #1
// → Deletes comments
// → Deletes user #5
// → Shows success message
```

## Authorization

All operations are protected by the `UserPolicy`:

### Policy Methods

| Method | Permission | Notes |
|--------|-----------|-------|
| `viewAny()` | All users | View user list |
| `view()` | All users | View user details |
| `create()` | Admin only | Create new users |
| `update()` | Admin only | Modify user roles |
| `delete()` | Admin only | Delete users (with restrictions) |
| `ban()` | Admin only | Ban/unban users (with restrictions) |

### Policy Rules

**Create:**
- Must be admin

**Update:**
- Must be admin
- Can modify any user (including role changes)

**Delete:**
- Must be admin
- Cannot delete self
- Cannot delete other admins

**Ban:**
- Must be admin
- Cannot ban self
- Cannot ban other admins

### Authorization Errors

When authorization fails, the component:
1. Catches `AuthorizationException`
2. Flashes error message to session
3. Returns early without executing action
4. Displays error in UI

**Example Error Messages:**
- "You are not authorized to perform this action."
- "You cannot delete your own account."
- "You cannot delete another administrator."
- "You cannot ban another administrator."

## Search and Filtering

### Search Functionality

**Trait:** `ManagesSearch`

**Features:**
- Real-time search with debouncing (400ms)
- Search across name and email fields
- URL persistence (`?search=term`)
- Clear search button
- Keyboard shortcut (⌘K)

**Implementation:**
```php
// Search is applied in with() method
$query->when($search !== '', function ($query) use ($search) {
    $query->where(function ($inner) use ($search) {
        $inner->where('name', 'like', '%'.$search.'%')
            ->orWhere('email', 'like', '%'.$search.'%');
    });
});
```

**Usage:**
```blade
<input
    type="search"
    wire:model.live.debounce.400ms="search"
    wire:keydown.enter.prevent="applySearchShortcut"
    placeholder="Search by name or email..."
/>
```

### Clear Filters

**Method:** `clearFilters()`

Resets:
- Search term
- Pagination to page 1

## Sorting

### Sortable Columns

**Trait:** `ManagesSorting`

**Available Columns:**
- `name` - User name (default)
- `email` - Email address
- `created_at` - Join date
- `posts_count` - Number of posts

**Default Sort:**
- Field: `name`
- Direction: `asc`

**URL Persistence:**
- Sort field: `?sort=email`
- Direction: `?direction=desc`

**Implementation:**
```php
protected function sortableColumns(): array
{
    return ['name', 'email', 'created_at', 'posts_count'];
}

protected function defaultSortField(): string
{
    return 'name';
}

protected function defaultSortDirection(): string
{
    return 'asc';
}
```

**Usage:**
```blade
<select wire:model.live="sortField">
    <option value="name">Name</option>
    <option value="email">Email</option>
    <option value="created_at">Join Date</option>
    <option value="posts_count">Posts</option>
</select>

<button wire:click="sortBy('{{ $sortField }}')">
    {{ $sortDirection === 'asc' ? 'Ascending' : 'Descending' }}
</button>
```

### Secondary Sorting

When sorting by a field other than `name`, a secondary sort by `name` is applied for consistency:

```php
if ($sortField !== 'name') {
    $query->orderBy('name');
}
```

## Validation

### Create Form Validation

**Real-time Validation:**
- Validates on field update (`wire:model.live`)
- Shows errors immediately
- Clears errors when corrected

**Validation Rules:**

| Field | Rules | Error Messages |
|-------|-------|----------------|
| Name | required, string, max:255 | "The name field is required." |
| Email | required, email, max:255, unique | "The email must be a valid email address." |
| Password | required, string, min:8, confirmed | "The password must be at least 8 characters." |
| Admin | boolean | - |
| Author | boolean | - |
| Banned | boolean | - |

**Format Hints:**
- Email: "Must be a valid email address (e.g., user@example.com)"
- Password: "Minimum 8 characters required"
- Email Unique: "Email must be unique."

**Example:**
```blade
<input
    type="email"
    wire:model.live="createForm.email"
/>
@error('createForm.email')
    <p class="text-xs text-rose-500">{{ $message }}</p>
@else
    <p class="text-xs text-slate-500">
        Must be a valid email address (e.g., user@example.com)
    </p>
@enderror
```

## Delete Strategies

### Transfer Strategy

**Default strategy** - Preserves content by transferring ownership.

**Process:**
1. Select target user from dropdown
2. Transfer all posts to target user
3. Delete all comments by deleted user
4. Delete user account

**Use Cases:**
- User leaving organization
- Consolidating accounts
- Preserving published content

**Example:**
```php
// Transfer posts to admin user
$this->deleteStrategy = 'transfer';
$this->transferTarget = 1;
$this->deleteUser();

// Result:
// - All posts transferred to user #1
// - All comments deleted
// - User account deleted
```

### Delete Strategy

**Destructive strategy** - Removes all content.

**Process:**
1. Find all posts by user
2. Delete all comments on those posts
3. Delete all posts
4. Delete all comments by user
5. Delete user account

**Use Cases:**
- Spam accounts
- Test accounts
- Complete removal required

**Warning:** This action cannot be undone!

**Example:**
```php
// Delete everything
$this->deleteStrategy = 'delete';
$this->deleteUser();

// Result:
// - All posts deleted
// - All comments on those posts deleted
// - All comments by user deleted
// - User account deleted
```

### Transfer Target Selection

**Available Targets:**
- All users except the one being deleted
- Sorted by name
- Defaults to current admin user

**Fallback:**
- If no target selected, defaults to current user
- If target is the deleted user, defaults to current user

## Keyboard Shortcuts

| Shortcut | Action | Context |
|----------|--------|---------|
| ⌘K | Focus search | Global |
| Enter | Apply search | Search input |
| Escape | Close modal | Modal open |
| Tab | Navigate form | Form fields |
| Space | Toggle checkbox | Checkbox focused |

## Accessibility

### ARIA Labels

All interactive elements have descriptive ARIA labels:

```blade
<input
    type="search"
    aria-label="Search users by name or email"
/>

<button
    wire:click="toggleAdmin({{ $user->id }})"
    aria-label="Toggle admin role for {{ $user->name }}"
/>

<table aria-label="Users table">
```

### Keyboard Navigation

- **Tab**: Navigate between interactive elements
- **Enter/Space**: Activate buttons and toggles
- **Arrow Keys**: Navigate table (browser default)
- **Escape**: Close modals

### Screen Reader Support

- Table has descriptive `aria-label`
- Loading states announced
- Success/error messages announced
- Modal titles announced on open
- Focus trapped in modals

### Focus Management

- Modal opens: Focus moves to first input
- Modal closes: Focus returns to trigger button
- Form submission: Focus on first error (if any)
- Visible focus indicators on all interactive elements

### Color Contrast

All text meets WCAG 2.1 AA standards:
- Body text: 4.5:1 minimum
- Large text: 3:1 minimum
- Interactive elements: 3:1 minimum

## Configuration

### Pagination

**Config:** `config/interface.php`

```php
'pagination' => [
    'admin' => [
        'default' => 20,
        'options' => [10, 20, 50, 100],
    ],
],
```

**Usage:**
```php
// Component uses ManagesPerPage trait
protected function perPageContext(): string
{
    return 'admin';
}
```

### Search Debounce

**Config:** `config/interface.php`

```php
'search_debounce_ms' => 300,
```

**Usage:**
```blade
<input
    wire:model.live.debounce.400ms="search"
/>
```

### Query String Persistence

**Configured in component:**
```php
protected $queryString = [
    'perPage' => ['except' => 20],
    'search' => ['except' => ''],
    'sortField' => ['as' => 'sort', 'except' => 'name'],
    'sortDirection' => ['as' => 'direction', 'except' => 'asc'],
];
```

**URL Examples:**
- `/admin/users` - Default view
- `/admin/users?search=john` - Search for "john"
- `/admin/users?sort=email&direction=desc` - Sort by email descending
- `/admin/users?page=2&perPage=50` - Page 2, 50 per page

## Translation Keys

All user-facing text uses translation keys from `lang/en/admin.php`:

### Common Keys

| Key | Default Value |
|-----|---------------|
| `admin.users.title` | "Users" |
| `admin.users.heading` | "Manage Users" |
| `admin.users.description` | "Manage user accounts, roles, and permissions." |
| `admin.users.create_button` | "Create User" |
| `admin.users.empty` | "No users found." |

### Role Keys

| Key | Default Value |
|-----|---------------|
| `admin.users.role_admin` | "Admin" |
| `admin.users.role_author` | "Author" |
| `admin.users.role_reader` | "Reader" |

### Status Keys

| Key | Default Value |
|-----|---------------|
| `admin.users.status_active` | "Active" |
| `admin.users.status_banned` | "Banned" |

### Action Keys

| Key | Default Value |
|-----|---------------|
| `admin.users.action_delete` | "Delete" |
| `admin.users.action_cancel` | "Cancel" |
| `admin.users.action_save` | "Save User" |
| `admin.users.action_confirm_delete` | "Confirm Delete" |

### Message Keys

| Key | Default Value |
|-----|---------------|
| `admin.users.created` | "User :name created successfully." |
| `admin.users.deleted` | "User :name deleted successfully." |
| `admin.users.made_admin` | ":name is now an admin." |
| `admin.users.banned` | ":name has been banned." |
| `admin.users.cannot_self_update` | "You cannot modify your own account." |

### Pluralization

Uses Laravel's `trans_choice()` for count-based messages:

```php
trans_choice('admin.users.posts_count', $count, ['count' => $count])
// 0 posts: "No posts"
// 1 post: "1 post"
// 2+ posts: "2 posts"
```

## Testing

### Feature Tests

**Location:** `tests/Feature/AdminUsersPageTest.php`

**Coverage:**
- User listing display
- Search functionality
- Sort functionality
- Create user workflow
- Role toggle operations
- Ban toggle operations
- Delete user workflow
- Authorization checks

**Example:**
```php
test('admin can create new user', function () {
    $admin = User::factory()->admin()->create();
    
    $this->actingAs($admin)
        ->livewire(UsersIndex::class)
        ->set('createForm.name', 'New User')
        ->set('createForm.email', 'new@example.com')
        ->set('createForm.password', 'password123')
        ->set('createForm.password_confirmation', 'password123')
        ->call('createUser')
        ->assertHasNoErrors();
    
    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'new@example.com',
    ]);
});
```

### Property Tests

**Location:** `tests/Unit/`

**Planned Tests:**
- Email uniqueness validation
- User role updates
- User ban status
- User deletion
- User search

### Browser Tests

**Location:** `tests/Browser/AdminUsersTest.php`

**Coverage:**
- Complete user creation flow
- Role toggle interactions
- Delete workflow with strategies
- Search and sort interactions
- Keyboard navigation

## Troubleshooting

### Common Issues

#### "You cannot modify your own account"

**Cause:** Attempting to change own admin/author status or ban self.

**Solution:** This is intentional. Use another admin account to modify your roles.

#### "You cannot delete another administrator"

**Cause:** Attempting to delete a user with admin role.

**Solution:** Remove admin role first, then delete. Or use another strategy.

#### Email already exists

**Cause:** Email address is already registered.

**Solution:** Use a different email address or update the existing user.

#### Search not working

**Cause:** JavaScript not loaded or Livewire connection issue.

**Solution:**
1. Check browser console for errors
2. Verify Livewire assets are loaded
3. Clear browser cache
4. Check network tab for failed requests

#### Sort not persisting

**Cause:** Query string not being updated.

**Solution:**
1. Verify `$queryString` property is configured
2. Check URL for sort parameters
3. Clear browser cache

### Debug Mode

Enable debug mode to see detailed error messages:

```env
APP_DEBUG=true
```

### Logging

User operations are logged automatically:

```php
// Check logs for user operations
tail -f storage/logs/laravel.log
```

## Related Documentation

- [Volt Component Guide](VOLT_COMPONENT_GUIDE.md)
- [Livewire Traits Guide](LIVEWIRE_TRAITS_GUIDE.md)
- [Admin Configuration](ADMIN_CONFIGURATION.md)
- [User Model API](../app/Models/User.php)
- [User Policy](../app/Policies/UserPolicy.php)

## Version History

- **v1.0.0** (2025-11-23) - Initial documentation
  - Complete users management interface
  - Role and ban management
  - Delete strategies
  - Search and sort functionality
  - Real-time validation

## Support

For questions or issues:
1. Check this documentation
2. Review related documentation
3. Check existing tests for examples
4. Review component source code

---

**Last Updated:** 2025-11-23
**Component Version:** 1.0.0
**Laravel Version:** 12.x
**Livewire Version:** 3.x
