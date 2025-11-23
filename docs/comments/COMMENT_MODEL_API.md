# Comment Model - Complete API Reference

**Version**: 3.0  
**Last Updated**: 2025-11-23  
**Namespace**: `App\Models\Comment`

---

## Table of Contents

1. [Overview](#overview)
2. [Properties](#properties)
3. [Relationships](#relationships)
4. [Query Scopes](#query-scopes)
5. [Status Methods](#status-methods)
6. [Permission Methods](#permission-methods)
7. [Spam Detection](#spam-detection)
8. [Accessors](#accessors)
9. [Usage Examples](#usage-examples)
10. [Performance Considerations](#performance-considerations)

---

## Overview

The `Comment` model represents user comments on blog posts with comprehensive moderation, spam detection, and audit trail capabilities.

### Key Features

- ✅ Soft deletes for data recovery
- ✅ Status-based moderation workflow (pending → approved/rejected)
- ✅ Audit trail for approvals (who approved, when)
- ✅ Spam detection heuristics
- ✅ IP address tracking with privacy masking
- ✅ User agent tracking for bot detection
- ✅ Comprehensive query scopes
- ✅ Permission-based access control

### Database Schema

```sql
CREATE TABLE comments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    approved_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX comments_status_created_at_index (status, created_at),
    INDEX comments_post_id_status_index (post_id, status),
    INDEX comments_user_id_created_at_index (user_id, created_at),
    INDEX comments_deleted_at_index (deleted_at),
    INDEX comments_ip_address_index (ip_address)
);
```

---

## Properties

### Mass Assignable Attributes

```php
protected $fillable = [
    'user_id',      // int - ID of the comment author
    'post_id',      // int - ID of the parent post
    'content',      // string - Comment text content
    'status',       // CommentStatus - Moderation status
    'ip_address',   // string|null - IPv4/IPv6 address
    'user_agent',   // string|null - Browser user agent
];
```

### Casts

```php
protected $casts = [
    'status' => CommentStatus::class,  // Enum casting
    'approved_at' => 'datetime',       // Carbon instance
];
```

### Default Values

```php
protected $attributes = [
    'status' => CommentStatus::Pending->value,  // New comments are pending
];
```

### Eager Loading

```php
protected $with = ['user'];  // Always eager load comment author
```

---

## Relationships

### `user(): BelongsTo`

Get the user who wrote the comment.

**Returns**: `BelongsTo<User, Comment>`

**Example**:
```php
$author = $comment->user;
echo $author->name;
```

**Foreign Key**: `user_id`  
**Cascade Rule**: `ON DELETE CASCADE`

---

### `post(): BelongsTo`

Get the post this comment belongs to.

**Returns**: `BelongsTo<Post, Comment>`

**Example**:
```php
$post = $comment->post;
echo $post->title;
```

**Foreign Key**: `post_id`  
**Cascade Rule**: `ON DELETE CASCADE`

---

### `approver(): BelongsTo`

Get the user who approved this comment.

**Returns**: `BelongsTo<User, Comment>`

**Example**:
```php
$approver = $comment->approver;
if ($approver) {
    echo "Approved by: {$approver->name}";
}
```

**Foreign Key**: `approved_by`  
**Cascade Rule**: `ON DELETE SET NULL`

---

## Query Scopes

### Status Filtering

#### `scopeApproved(Builder $query): Builder`

Filter to only approved comments.

**Example**:
```php
$approved = Comment::approved()->get();
```

**SQL**: `WHERE status = 'approved'`  
**Index Used**: `comments_status_created_at_index`

---

#### `scopePending(Builder $query): Builder`

Filter to only pending comments.

**Example**:
```php
$pending = Comment::pending()->get();
```

**SQL**: `WHERE status = 'pending'`  
**Index Used**: `comments_status_created_at_index`

---

#### `scopeRejected(Builder $query): Builder`

Filter to only rejected comments.

**Example**:
```php
$rejected = Comment::rejected()->get();
```

**SQL**: `WHERE status = 'rejected'`  
**Index Used**: `comments_status_created_at_index`

---

#### `scopeWithStatus(Builder $query, ?CommentStatus $status): Builder`

Filter by status, or return all if null.

**Parameters**:
- `$status` (CommentStatus|null) - Status to filter by, or null for all

**Example**:
```php
$comments = Comment::withStatus($status)->get();
```

**Use Case**: Dynamic filtering in admin interfaces

---

#### `scopeAwaitingModeration(Builder $query): Builder`

Semantic alias for `pending()`. Better for UI/UX clarity.

**Example**:
```php
$queue = Comment::awaitingModeration()->get();
```

---

### Ordering

#### `scopeOrderByDate(Builder $query, string $direction = 'desc'): Builder`

Order comments by creation date.

**Parameters**:
- `$direction` (string) - 'desc' for newest first, 'asc' for oldest first

**Example**:
```php
$comments = Comment::orderByDate('asc')->get();
```

**SQL**: `ORDER BY created_at {$direction}`

---

#### `scopeLatest(Builder $query): Builder`

Order comments by newest first.

**Example**:
```php
$recent = Comment::latest()->get();
```

**SQL**: `ORDER BY created_at DESC`  
**Index Used**: `comments_status_created_at_index` (when combined with status)

---

#### `scopeOldest(Builder $query): Builder`

Order comments by oldest first.

**Example**:
```php
$oldest = Comment::oldest()->get();
```

**SQL**: `ORDER BY created_at ASC`

---

### Filtering

#### `scopeForPost(Builder $query, int|Post $post): Builder`

Filter comments for a specific post.

**Parameters**:
- `$post` (int|Post) - Post ID or Post model instance

**Example**:
```php
$comments = Comment::forPost($post)->approved()->get();
$comments = Comment::forPost(123)->approved()->get();
```

**SQL**: `WHERE post_id = ?`  
**Index Used**: `comments_post_id_status_index`

---

#### `scopeByUser(Builder $query, int $userId): Builder`

Filter comments by a specific user.

**Parameters**:
- `$userId` (int) - User ID

**Example**:
```php
$userComments = Comment::byUser($userId)->latest()->get();
```

**SQL**: `WHERE user_id = ?`  
**Index Used**: `comments_user_id_created_at_index`

---

#### `scopeRecent(Builder $query, int $limit = 10): Builder`

Get recent comments with a limit.

**Parameters**:
- `$limit` (int) - Number of comments to retrieve (default: 10)

**Example**:
```php
$recent = Comment::recent(5)->get();
```

**SQL**: `ORDER BY created_at DESC LIMIT ?`

---

#### `scopeFromIp(Builder $query, string $ipAddress): Builder`

Filter comments from a specific IP address. Useful for spam detection.

**Parameters**:
- `$ipAddress` (string) - IP address to filter by

**Example**:
```php
$ipComments = Comment::fromIp('192.168.1.100')->get();
```

**SQL**: `WHERE ip_address = ?`  
**Index Used**: `comments_ip_address_index`

---

#### `scopeApprovedBetween(Builder $query, string $from, ?string $to = null): Builder`

Get comments approved within a date range. Useful for analytics.

**Parameters**:
- `$from` (string) - Start date (Y-m-d format)
- `$to` (string|null) - End date (Y-m-d format), defaults to today

**Example**:
```php
$monthly = Comment::approvedBetween('2025-01-01', '2025-01-31')->count();
$today = Comment::approvedBetween(now()->format('Y-m-d'))->count();
```

**SQL**: `WHERE status = 'approved' AND approved_at BETWEEN ? AND ?`

---

## Status Methods

### `isApproved(): bool`

Check if the comment is approved.

**Returns**: `bool`

**Example**:
```php
if ($comment->isApproved()) {
    echo "This comment is visible to the public.";
}
```

---

### `isPending(): bool`

Check if the comment is pending moderation.

**Returns**: `bool`

**Example**:
```php
if ($comment->isPending()) {
    echo "This comment is awaiting moderation.";
}
```

---

### `isRejected(): bool`

Check if the comment is rejected.

**Returns**: `bool`

**Example**:
```php
if ($comment->isRejected()) {
    echo "This comment was rejected by a moderator.";
}
```

---

### `approve(?User $approver = null): bool`

Approve the comment and track the approver.

**Parameters**:
- `$approver` (User|null) - The user approving the comment (optional)

**Returns**: `bool` - True if status changed, false if already approved

**Side Effects**:
- Sets `status` to `CommentStatus::Approved`
- Sets `approved_at` to current timestamp
- Sets `approved_by` to approver's ID (if provided)
- Saves the model

**Example**:
```php
if ($comment->approve(auth()->user())) {
    // Status changed - send notification
    $comment->user->notify(new CommentApprovedNotification($comment));
    
    return redirect()->back()->with('success', 'Comment approved!');
}

return redirect()->back()->with('info', 'Comment was already approved.');
```

**Idempotent**: Yes - safe to call multiple times

---

### `reject(): bool`

Reject the comment.

**Returns**: `bool` - True if status changed, false if already rejected

**Side Effects**:
- Sets `status` to `CommentStatus::Rejected`
- Saves the model

**Example**:
```php
if ($comment->reject()) {
    Log::info('Comment rejected', ['comment_id' => $comment->id]);
    
    return redirect()->back()->with('success', 'Comment rejected!');
}

return redirect()->back()->with('info', 'Comment was already rejected.');
```

**Idempotent**: Yes - safe to call multiple times

---

### `markPending(): bool`

Mark the comment as pending (for re-review workflows).

**Returns**: `bool` - True if status changed, false if already pending

**Side Effects**:
- Sets `status` to `CommentStatus::Pending`
- Saves the model

**Example**:
```php
if ($comment->markPending()) {
    return redirect()->back()->with('success', 'Comment marked for re-review!');
}
```

**Idempotent**: Yes - safe to call multiple times

---

## Permission Methods

### `canBeEditedBy(User $user): bool`

Check if the comment can be edited by the given user.

**Parameters**:
- `$user` (User) - User to check permissions for

**Returns**: `bool`

**Logic**: Admin users or the comment author can edit

**Example**:
```php
@if($comment->canBeEditedBy(auth()->user()))
    <a href="{{ route('comments.edit', $comment) }}">Edit</a>
@endif
```

---

### `canBeDeletedBy(User $user): bool`

Check if the comment can be deleted by the given user.

**Parameters**:
- `$user` (User) - User to check permissions for

**Returns**: `bool`

**Logic**: Admin users or the comment author can delete

**Example**:
```php
@if($comment->canBeDeletedBy(auth()->user()))
    <form method="POST" action="{{ route('comments.destroy', $comment) }}">
        @csrf @method('DELETE')
        <button type="submit">Delete</button>
    </form>
@endif
```

---

## Spam Detection

### `isPotentialSpam(): bool`

Check if this comment might be spam based on heuristics.

**Returns**: `bool`

**Heuristics**:
1. **Excessive links**: More than 3 HTTP links
2. **Excessive uppercase**: More than 50% uppercase letters
3. **Too short**: Less than 3 characters
4. **IP frequency**: More than 10 comments from same IP

**Example**:
```php
if ($comment->isPotentialSpam()) {
    $comment->reject();
    
    Log::warning('Spam detected', [
        'comment_id' => $comment->id,
        'ip_address' => $comment->ip_address,
        'content_preview' => Str::limit($comment->content, 50),
    ]);
    
    return redirect()->back()->with('warning', 'Comment flagged as spam.');
}
```

**Note**: This is a basic implementation. Consider using dedicated spam detection services like Akismet for production.

---

### `getCommentsFromSameIpCount(): int`

Get the count of comments from the same IP address (excluding current comment).

**Returns**: `int`

**Example**:
```php
$count = $comment->getCommentsFromSameIpCount();

if ($count > 10) {
    Log::warning('High comment frequency from IP', [
        'ip_address' => $comment->ip_address,
        'count' => $count,
    ]);
}
```

**Use Cases**:
- Rate limiting
- Spam detection
- Abuse prevention

---

## Accessors

### `getFormattedDateAttribute(): string`

Get the formatted creation date in human-readable format.

**Returns**: `string`

**Example**:
```php
echo $comment->formatted_date;  // "2 hours ago"
```

**Blade**:
```blade
<small>{{ $comment->formatted_date }}</small>
```

---

### `getMaskedIpAttribute(): ?string`

Get a privacy-compliant masked version of the IP address.

**Returns**: `string|null`

**Masking Rules**:
- **IPv4**: Masks last octet (e.g., `192.168.1.xxx`)
- **IPv6**: Masks last segment (e.g., `2001:db8::xxxx`)

**Example**:
```php
echo $comment->masked_ip;  // "192.168.1.xxx"
```

**Blade**:
```blade
<small>IP: {{ $comment->masked_ip }}</small>
```

**Use Cases**:
- Admin interfaces
- Moderation logs
- GDPR/CCPA compliance

---

## Usage Examples

### Creating a Comment

```php
use Illuminate\Support\Facades\Request;

$comment = Comment::create([
    'user_id' => auth()->id(),
    'post_id' => $post->id,
    'content' => $request->input('content'),
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);

// Check for spam
if ($comment->isPotentialSpam()) {
    $comment->reject();
    return back()->with('warning', 'Your comment has been flagged for review.');
}

return back()->with('success', 'Comment submitted for moderation!');
```

---

### Moderation Queue

```php
// Get all pending comments with relationships
$queue = Comment::awaitingModeration()
    ->with(['user', 'post', 'approver'])
    ->latest()
    ->paginate(20);

// Display in admin panel
foreach ($queue as $comment) {
    echo "{$comment->user->name} on {$comment->post->title}";
    echo "IP: {$comment->masked_ip}";
    
    if ($comment->isPotentialSpam()) {
        echo " [POTENTIAL SPAM]";
    }
}
```

---

### Bulk Approval

```php
$commentIds = $request->input('comment_ids');
$admin = auth()->user();

$approved = 0;
foreach ($commentIds as $id) {
    $comment = Comment::find($id);
    
    if ($comment && $comment->approve($admin)) {
        $approved++;
    }
}

return back()->with('success', "{$approved} comments approved!");
```

---

### Analytics

```php
// Comments approved this month
$thisMonth = Comment::approvedBetween(
    now()->startOfMonth()->format('Y-m-d'),
    now()->endOfMonth()->format('Y-m-d')
)->count();

// Comments by status
$stats = [
    'approved' => Comment::approved()->count(),
    'pending' => Comment::pending()->count(),
    'rejected' => Comment::rejected()->count(),
];

// Top commenters
$topCommenters = Comment::approved()
    ->select('user_id', DB::raw('COUNT(*) as comment_count'))
    ->groupBy('user_id')
    ->orderByDesc('comment_count')
    ->limit(10)
    ->with('user')
    ->get();
```

---

### Spam Detection Workflow

```php
// Automatic spam detection on creation
class CommentObserver
{
    public function created(Comment $comment): void
    {
        if ($comment->isPotentialSpam()) {
            $comment->reject();
            
            // Notify moderators
            Notification::send(
                User::where('is_admin', true)->get(),
                new SpamCommentDetected($comment)
            );
        }
    }
}
```

---

## Performance Considerations

### Indexed Queries

These queries are optimized with composite indexes:

```php
// ✅ Fast - uses comments_status_created_at_index
Comment::approved()->latest()->get();

// ✅ Fast - uses comments_post_id_status_index
Comment::forPost($post)->approved()->get();

// ✅ Fast - uses comments_user_id_created_at_index
Comment::byUser($userId)->latest()->get();

// ✅ Fast - uses comments_ip_address_index
Comment::fromIp($ip)->get();
```

### Avoiding N+1 Queries

```php
// ❌ Bad - N+1 queries
$comments = Comment::approved()->get();
foreach ($comments as $comment) {
    echo $comment->user->name;      // N queries
    echo $comment->post->title;     // N queries
    echo $comment->approver->name;  // N queries
}

// ✅ Good - 2 queries total
$comments = Comment::with(['user', 'post', 'approver'])
    ->approved()
    ->get();
    
foreach ($comments as $comment) {
    echo $comment->user->name;      // No query
    echo $comment->post->title;     // No query
    echo $comment->approver?->name; // No query
}
```

### Pagination

```php
// ✅ Always paginate large result sets
$comments = Comment::approved()
    ->with('user')
    ->latest()
    ->paginate(20);  // Not get()
```

---

## Testing

### Factory Usage

```php
// Create basic comment
$comment = Comment::factory()->create();

// Create approved comment with approver
$comment = Comment::factory()->approved()->create();

// Create comment for specific post
$comment = Comment::factory()
    ->for($post)
    ->create();

// Create comment with spam indicators
$comment = Comment::factory()->create([
    'content' => 'Check out http://spam1.com http://spam2.com http://spam3.com http://spam4.com',
]);
```

### Property-Based Testing

```php
test('approve method is idempotent', function () {
    $comment = Comment::factory()->create(['status' => CommentStatus::Pending]);
    $admin = User::factory()->create(['is_admin' => true]);
    
    expect($comment->approve($admin))->toBeTrue();  // First call
    expect($comment->approve($admin))->toBeFalse(); // Second call
    expect($comment->isApproved())->toBeTrue();
});

test('spam detection identifies excessive links', function () {
    $comment = Comment::factory()->create([
        'content' => str_repeat('http://spam.com ', 5),
    ]);
    
    expect($comment->isPotentialSpam())->toBeTrue();
});
```

---

## Related Documentation

- **Quick Reference**: `COMMENT_MODEL_QUICK_REFERENCE.md`
- **Architecture**: `docs/comments/COMMENT_MODEL_ARCHITECTURE.md`
- **Improvements Summary**: `COMMENT_MODEL_IMPROVEMENTS_SUMMARY.md`
- **Schema Diagram**: `COMMENT_MODEL_SCHEMA_DIAGRAM.md`
- **Analysis**: `COMMENT_MODEL_ANALYSIS.md`

---

## Changelog

### Version 3.0 (2025-11-23)
- Added spam detection methods
- Added IP address tracking and masking
- Added audit trail for approvals
- Added comprehensive query scopes
- Enhanced documentation

### Version 2.0 (2025-11-23)
- Added soft deletes
- Refactored status transition methods
- Added permission methods
- Improved query scopes

### Version 1.0 (Initial)
- Basic comment functionality
- Status-based moderation

---

**Maintained By**: Database Architecture Team  
**Last Review**: 2025-11-23  
**Next Review**: 2025-02-23
