# Comment Model - Quick Reference Card

**Last Updated**: 2025-11-23  
**Version**: 3.0 (Enhanced with Spam Detection & Audit Trail)

---

## 🚀 New Features

### Soft Deletes
```php
$comment->delete();           // Soft delete (preserves data)
$comment->restore();          // Restore deleted comment
$comment->forceDelete();      // Permanent delete

Comment::onlyTrashed()->get(); // Get only deleted
Comment::withTrashed()->get(); // Get all including deleted
```

### Status Transitions with Audit Trail
```php
// Approve with audit trail
$comment->approve($admin);    // Returns true if status changed
// Sets: status='approved', approved_at=now(), approved_by=$admin->id

$comment->reject();           // Returns true if status changed
$comment->markPending();      // Returns true if status changed

// All methods are idempotent (safe to call multiple times)
// Approval tracks timestamp and approver for accountability

// Access audit trail
$approver = $comment->approver;        // User who approved
$approvedAt = $comment->approved_at;   // When approved
```

### Spam Detection & Moderation
```php
// Check if comment might be spam
if ($comment->isPotentialSpam()) {
    $comment->reject();
}

// Get comments from same IP (spam detection)
$count = $comment->getCommentsFromSameIpCount();

// Get masked IP for privacy-compliant display
$maskedIp = $comment->masked_ip; // "192.168.1.xxx"

// Filter by IP address
Comment::fromIp('192.168.1.1')->get();
```

### Enhanced Query Scopes
```php
// Filter by post
Comment::forPost($post)->get();
Comment::forPost($postId)->get();

// Filter by user
Comment::byUser($userId)->get();

// Get recent comments
Comment::recent(10)->get();

// Flexible date ordering
Comment::orderByDate('desc')->get();  // newest first
Comment::orderByDate('asc')->get();   // oldest first
```

### Helper Methods
```php
// Permission checks
$comment->canBeEditedBy($user);   // Returns bool
$comment->canBeDeletedBy($user);  // Returns bool

// Formatted date
$comment->formatted_date;          // "2 hours ago"
```

---

## 📖 Common Usage Patterns

### Get Approved Comments for a Post
```php
// Before
$comments = Comment::where('post_id', $post->id)
    ->where('status', CommentStatus::Approved)
    ->orderBy('created_at', 'desc')
    ->get();

// After (cleaner, faster with indexes)
$comments = Comment::forPost($post)
    ->approved()
    ->latest()
    ->get();
```

### Approve a Comment
```php
// Before
$comment->status = CommentStatus::Approved;
$comment->save();

// After (idempotent, extensible)
if ($comment->approve()) {
    // Status changed, send notification
    $comment->user->notify(new CommentApprovedNotification($comment));
}
```

### Check Edit Permission in Blade
```php
// Before
@if(auth()->user()->is_admin || auth()->id() === $comment->user_id)
    <a href="{{ route('comments.edit', $comment) }}">Edit</a>
@endif

// After (cleaner, testable)
@if($comment->canBeEditedBy(auth()->user()))
    <a href="{{ route('comments.edit', $comment) }}">Edit</a>
@endif
```

### Get User's Recent Comments
```php
// Before
$comments = Comment::where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// After (cleaner)
$comments = Comment::byUser($userId)->recent(10)->get();
```

---

## 🔍 Query Scopes Reference

| Scope | Parameters | Description | Example |
|-------|-----------|-------------|---------|
| `approved()` | - | Only approved comments | `Comment::approved()->get()` |
| `pending()` | - | Only pending comments | `Comment::pending()->get()` |
| `rejected()` | - | Only rejected comments | `Comment::rejected()->get()` |
| `withStatus()` | `?CommentStatus` | Filter by status (null = all) | `Comment::withStatus($status)->get()` |
| `forPost()` | `int\|Post` | Comments for specific post | `Comment::forPost($post)->get()` |
| `byUser()` | `int` | Comments by specific user | `Comment::byUser($userId)->get()` |
| `recent()` | `int` (default 10) | Recent comments with limit | `Comment::recent(5)->get()` |
| `orderByDate()` | `string` (desc/asc) | Order by date | `Comment::orderByDate('asc')->get()` |
| `latest()` | - | Newest first | `Comment::latest()->get()` |
| `oldest()` | - | Oldest first | `Comment::oldest()->get()` |
| `fromIp()` | `string` | Comments from specific IP | `Comment::fromIp('192.168.1.1')->get()` |
| `awaitingModeration()` | - | Alias for pending() | `Comment::awaitingModeration()->get()` |
| `approvedBetween()` | `string`, `?string` | Approved in date range | `Comment::approvedBetween('2025-01-01', '2025-01-31')->get()` |

---

## 🎯 Status Check Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `isApproved()` | `bool` | Check if comment is approved |
| `isPending()` | `bool` | Check if comment is pending |
| `isRejected()` | `bool` | Check if comment is rejected |

---

## 🔐 Permission Methods

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `canBeEditedBy()` | `User` | `bool` | Check if user can edit |
| `canBeDeletedBy()` | `User` | `bool` | Check if user can delete |

## 🛡️ Spam Detection Methods

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `isPotentialSpam()` | - | `bool` | Check if comment might be spam |
| `getCommentsFromSameIpCount()` | - | `int` | Count comments from same IP |
| `getMaskedIpAttribute()` | - | `?string` | Get privacy-masked IP address |

---

## 📊 Performance Tips

### Use Composite Indexes
The following queries are optimized with composite indexes:

```php
// ✅ Fast (uses comments_status_created_at_index)
Comment::approved()->latest()->get();

// ✅ Fast (uses comments_post_id_status_index)
Comment::forPost($post)->approved()->get();

// ✅ Fast (uses comments_user_id_created_at_index)
Comment::byUser($userId)->latest()->get();

// ✅ Fast (uses comments_deleted_at_index)
Comment::onlyTrashed()->get();
```

### Avoid N+1 Queries
```php
// ❌ Bad (N+1 queries)
$comments = Comment::approved()->get();
foreach ($comments as $comment) {
    echo $comment->user->name;  // N queries
}

// ✅ Good (2 queries)
$comments = Comment::with('user')->approved()->get();
foreach ($comments as $comment) {
    echo $comment->user->name;  // No additional queries
}
```

---

## 🧪 Testing Examples

### Property-Based Testing
```php
// Test status transitions are idempotent
$comment = Comment::factory()->create(['status' => CommentStatus::Pending]);

$this->assertTrue($comment->approve());  // First call returns true
$this->assertFalse($comment->approve()); // Second call returns false (idempotent)
$this->assertTrue($comment->isApproved());
```

### Factory Usage
```php
// Create comment with relationships
$comment = Comment::factory()
    ->for($user)
    ->for($post)
    ->create(['status' => CommentStatus::Approved]);

// Create approved comment
$comment = Comment::factory()->approved()->create();

// Create rejected comment
$comment = Comment::factory()->rejected()->create();
```

---

## 🔄 Migration Commands

```bash
# Apply migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback --step=2

# Check migration status
php artisan migrate:status
```

---

## 📝 Code Examples

### Controller Example
```php
public function approve(Comment $comment)
{
    $this->authorize('update', $comment);
    
    if ($comment->approve()) {
        // Status changed
        event(new CommentApproved($comment));
        
        return back()->with('success', 'Comment approved successfully.');
    }
    
    return back()->with('info', 'Comment was already approved.');
}
```

### Livewire Component Example
```php
public function approveComment($commentId)
{
    $comment = Comment::findOrFail($commentId);
    $this->authorize('update', $comment);
    
    if ($comment->approve()) {
        $this->dispatch('comment-approved', commentId: $commentId);
    }
}
```

### Blade View Example
```blade
@foreach($comments as $comment)
    <div class="comment">
        <p>{{ $comment->content }}</p>
        <small>{{ $comment->formatted_date }}</small>
        
        @if($comment->canBeEditedBy(auth()->user()))
            <a href="{{ route('comments.edit', $comment) }}">Edit</a>
        @endif
        
        @if($comment->canBeDeletedBy(auth()->user()))
            <form method="POST" action="{{ route('comments.destroy', $comment) }}">
                @csrf
                @method('DELETE')
                <button type="submit">Delete</button>
            </form>
        @endif
    </div>
@endforeach
```

---

## 🚨 Important Notes

### Soft Deletes
- `delete()` now soft deletes by default
- Use `forceDelete()` for permanent deletion
- Soft deleted comments are excluded from normal queries
- Use `withTrashed()` or `onlyTrashed()` to include deleted comments

### Status Transitions
- All transition methods are idempotent
- They return `true` if status changed, `false` if already in that status
- Perfect for conditional logic and event dispatching

### Performance
- Composite indexes significantly improve query performance
- Always use scopes for common query patterns
- Eager load relationships to avoid N+1 queries

---

## 📚 Related Documentation

- **Full Analysis**: `REFACTORING_COMMENT_MODEL.md`
- **Summary**: `REFACTORING_SUMMARY.md`
- **Tests**: `tests/Unit/Comment*PropertyTest.php`
- **Migrations**: `database/migrations/2025_11_23_*`

---

**Quick Links**:
- [Laravel Soft Deletes](https://laravel.com/docs/eloquent#soft-deleting)
- [Query Scopes](https://laravel.com/docs/eloquent#query-scopes)
- [Eloquent Relationships](https://laravel.com/docs/eloquent-relationships)
