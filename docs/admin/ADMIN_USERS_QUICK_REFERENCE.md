# Admin Users Quick Reference

Quick reference guide for common admin users management tasks.

## Quick Links

- [Full Documentation](ADMIN_USERS_MANAGEMENT.md)
- [API Reference](../api/ADMIN_USERS_API.md)
- [Component Source](../resources/views/livewire/admin/users/index.blade.php)
- [User Model](../app/Models/User.php)
- [User Policy](../app/Policies/UserPolicy.php)

## Common Tasks

### Create a User

```php
// 1. Click "Create User" button
$this->openCreateModal();

// 2. Fill form
$this->createForm = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'is_admin' => false,
    'is_author' => true,
    'is_banned' => false,
];

// 3. Submit
$this->createUser();
```

### Toggle User Roles

```php
// Make user an admin
$this->toggleAdmin($user);

// Make user an author
$this->toggleAuthor($user);

// Ban user
$this->toggleBan($user);
```

### Delete User

```php
// 1. Confirm deletion
$this->confirmDelete($user);

// 2. Choose strategy
$this->deleteStrategy = 'transfer'; // or 'delete'
$this->transferTarget = 1; // if transfer

// 3. Execute
$this->deleteUser();
```

### Search Users

```php
// Set search term
$this->search = 'john';

// Clear search
$this->clearSearch();
```

### Sort Users

```php
// Sort by field
$this->sortBy('email');

// Toggle direction
$this->sortBy('email'); // Toggles asc/desc
```

## User Roles

| Role | Capabilities |
|------|-------------|
| **Admin** | Full system access, manage all users and content |
| **Author** | Create and publish posts, moderate own comments |
| **Reader** | View content, post comments (default) |

## User Status

| Status | Description |
|--------|-------------|
| **Active** | Normal user account |
| **Banned** | Cannot log in or interact |

## Validation Rules

| Field | Rules |
|-------|-------|
| Name | required, string, max:255 |
| Email | required, email, max:255, unique |
| Password | required, string, min:8, confirmed |
| Admin | boolean |
| Author | boolean |
| Banned | boolean |

## Authorization Matrix

| Action | Permission | Restrictions |
|--------|-----------|--------------|
| View List | All users | - |
| Create | Admin only | - |
| Update Roles | Admin only | Cannot modify self |
| Delete | Admin only | Cannot delete self or other admins |
| Ban | Admin only | Cannot ban self or other admins |

## Delete Strategies

### Transfer (Default)

```php
$this->deleteStrategy = 'transfer';
$this->transferTarget = 1; // Target user ID
```

**Process:**
1. Transfer all posts to target user
2. Delete all comments by user
3. Delete user account

**Use Case:** Preserve content when user leaves

### Delete

```php
$this->deleteStrategy = 'delete';
```

**Process:**
1. Delete all posts by user
2. Delete all comments on those posts
3. Delete all comments by user
4. Delete user account

**Use Case:** Complete removal (spam, test accounts)

## Sortable Columns

| Column | Field | Default |
|--------|-------|---------|
| Name | `name` | ✓ |
| Email | `email` | |
| Join Date | `created_at` | |
| Posts | `posts_count` | |

**Default Sort:** Name (ascending)

## Search Fields

- User name
- Email address

**Debounce:** 400ms

## URL Parameters

| Parameter | Type | Default | Example |
|-----------|------|---------|---------|
| `page` | int | 1 | `?page=2` |
| `perPage` | int | 20 | `?perPage=50` |
| `search` | string | '' | `?search=john` |
| `sort` | string | 'name' | `?sort=email` |
| `direction` | string | 'asc' | `?direction=desc` |

**Example URL:**
```
/admin/users?search=john&sort=email&direction=desc&page=2&perPage=50
```

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| ⌘K | Focus search |
| Enter | Apply search |
| Escape | Close modal |
| Tab | Navigate form |
| Space | Toggle checkbox |

## Translation Keys

### Common

```php
__('admin.users.title')              // "Users"
__('admin.users.heading')            // "Manage Users"
__('admin.users.create_button')      // "Create User"
__('admin.users.empty')              // "No users found."
```

### Roles

```php
__('admin.users.role_admin')         // "Admin"
__('admin.users.role_author')        // "Author"
__('admin.users.role_reader')        // "Reader"
```

### Status

```php
__('admin.users.status_active')      // "Active"
__('admin.users.status_banned')      // "Banned"
```

### Messages

```php
__('admin.users.created', ['name' => $name])
// "User :name created successfully."

__('admin.users.deleted', ['name' => $name])
// "User :name deleted successfully."

__('admin.users.made_admin', ['name' => $name])
// ":name is now an admin."

__('admin.users.banned', ['name' => $name])
// ":name has been banned."

__('admin.users.cannot_self_update')
// "You cannot modify your own account."
```

### Pluralization

```php
trans_choice('admin.users.posts_count', $count, ['count' => $count])
// 0: "No posts"
// 1: "1 post"
// 2+: "2 posts"

trans_choice('admin.users.comments_count', $count, ['count' => $count])
// 0: "No comments"
// 1: "1 comment"
// 2+: "2 comments"
```

## Configuration

### Pagination

**File:** `config/interface.php`

```php
'pagination' => [
    'admin' => [
        'default' => 20,
        'options' => [10, 20, 50, 100],
    ],
],
```

### Search Debounce

**File:** `config/interface.php`

```php
'search_debounce_ms' => 300,
```

**Usage:**
```blade
wire:model.live.debounce.400ms="search"
```

## Common Errors

### "You cannot modify your own account"

**Cause:** Attempting to change own roles or ban self

**Solution:** Use another admin account

### "You cannot delete another administrator"

**Cause:** Attempting to delete admin user

**Solution:** Remove admin role first, then delete

### "Email already exists"

**Cause:** Email address already registered

**Solution:** Use different email or update existing user

## Testing Examples

### Create User Test

```php
test('admin can create user', function () {
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

### Toggle Role Test

```php
test('admin can toggle author role', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_author' => false]);
    
    Livewire::actingAs($admin)
        ->test(UsersIndex::class)
        ->call('toggleAuthor', $user->id)
        ->assertHasNoErrors();
    
    expect($user->fresh()->is_author)->toBeTrue();
});
```

### Delete User Test

```php
test('admin can delete user with transfer', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $post = Post::factory()->for($user)->create();
    
    Livewire::actingAs($admin)
        ->test(UsersIndex::class)
        ->call('confirmDelete', $user->id)
        ->set('deleteStrategy', 'transfer')
        ->set('transferTarget', $admin->id)
        ->call('deleteUser')
        ->assertHasNoErrors();
    
    expect(User::find($user->id))->toBeNull();
    expect(Post::find($post->id)->user_id)->toBe($admin->id);
});
```

## Blade Component Usage

### Search Input

```blade
<input
    type="search"
    wire:model.live.debounce.400ms="search"
    wire:keydown.enter.prevent="applySearchShortcut"
    placeholder="{{ __('admin.users.search_placeholder') }}"
    aria-label="Search users by name or email"
/>
```

### Sort Dropdown

```blade
<select wire:model.live="sortField">
    <option value="name">{{ __('admin.users.sort.name') }}</option>
    <option value="email">{{ __('admin.users.sort.email') }}</option>
    <option value="created_at">{{ __('admin.users.sort.joined') }}</option>
    <option value="posts_count">{{ __('admin.users.sort.posts') }}</option>
</select>
```

### Role Toggle

```blade
<flux:switch
    wire:click="toggleAdmin({{ $user->id }})"
    :checked="$user->is_admin"
    :disabled="$user->is(auth()->user())"
    aria-label="{{ __('admin.users.action_toggle_admin', ['name' => $user->name]) }}"
/>
```

### Delete Button

```blade
<flux:button
    color="red"
    icon="trash"
    wire:click="confirmDelete({{ $user->id }})"
>
    {{ __('admin.users.action_delete') }}
</flux:button>
```

## Database Queries

### Main Query

```php
User::query()
    ->withCount(['posts', 'comments'])
    ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%"))
    ->orderBy($sortField, $sortDirection)
    ->paginate($perPage);
```

### Eager Loading

```php
->withCount([
    'posts as posts_count' => fn ($query) => $query->withoutGlobalScopes(),
    'comments',
])
```

## Performance Tips

1. **Always eager load relationships** to prevent N+1 queries
2. **Use debouncing** for search inputs (400ms recommended)
3. **Paginate results** to limit query size
4. **Add indexes** on frequently searched columns
5. **Use secondary sorting** for consistent results

## Security Checklist

- ✓ All operations protected by policies
- ✓ Passwords hashed with bcrypt
- ✓ CSRF protection enabled
- ✓ Mass assignment protection
- ✓ Cannot modify own admin status
- ✓ Cannot delete self
- ✓ Cannot delete other admins
- ✓ Cannot ban other admins

## Accessibility Checklist

- ✓ ARIA labels on all inputs
- ✓ Keyboard navigation support
- ✓ Focus management in modals
- ✓ Screen reader announcements
- ✓ Color contrast meets WCAG AA
- ✓ Loading states announced
- ✓ Error messages announced

## Related Files

| File | Purpose |
|------|---------|
| `resources/views/livewire/admin/users/index.blade.php` | Component |
| `app/Models/User.php` | User model |
| `app/Policies/UserPolicy.php` | Authorization |
| `lang/en/admin.php` | Translations |
| `config/interface.php` | Configuration |
| `tests/Feature/AdminUsersPageTest.php` | Tests |

## Support

- [Full Documentation](ADMIN_USERS_MANAGEMENT.md)
- [API Reference](../api/ADMIN_USERS_API.md)
- [Volt Component Guide](../volt/VOLT_COMPONENT_GUIDE.md)
- [Livewire Traits Guide](../livewire/LIVEWIRE_TRAITS_GUIDE.md)

---

**Last Updated:** 2025-11-23
**Version:** 1.0.0
