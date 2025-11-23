# Comment Model - Practical Usage Guide

**Version**: 3.0  
**Last Updated**: 2025-11-23  
**Audience**: Developers implementing comment features

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Common Workflows](#common-workflows)
3. [Controller Examples](#controller-examples)
4. [Livewire Component Examples](#livewire-component-examples)
5. [Blade Template Examples](#blade-template-examples)
6. [Event Handling](#event-handling)
7. [Spam Prevention](#spam-prevention)
8. [Testing Strategies](#testing-strategies)
9. [Troubleshooting](#troubleshooting)

---

## Getting Started

### Installation

The Comment model is included in the base installation. Ensure migrations are run:

```bash
php artisan migrate
```

### Basic Configuration

No additional configuration required. The model uses sensible defaults:
- New comments are `pending` by default
- Soft deletes are enabled
- User relationship is eager loaded

---

## Common Workflows

### 1. Public Comment Submission

**User Story**: A visitor submits a comment on a blog post.

```php
// routes/web.php
Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
    ->middleware('auth')
    ->name('comments.store');

// app/Http/Controllers/CommentController.php
public function store(StoreCommentRequest $request, Post $post)
{
    $comment = Comment::create([
        'user_id' => auth()->id(),
        'post_id' => $post->id,
        'content' => $request->validated('content'),
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
    ]);

    // Automatic spam detection
    if ($comment->isPotentialSpam()) {
        $comment->reject();
        
        return back()->with('warning', 
            'Your comment has been flagged for review. A moderator will review it shortly.'
        );
    }

    return back()->with('success', 
        'Your comment has been submitted and is awaiting moderation.'
    );
}
```

---

### 2. Admin Moderation Queue

**User Story**: An admin reviews pending comments.

```php
// routes/web.php
Route::get('/admin/comments', [AdminCommentController::class, 'index'])
    ->middleware(['auth', 'admin'])
    ->name('admin.comments.index');

// app/Http/Controllers/AdminCommentController.php
public function index(Request $request)
{
    $status = $request->enum('status', CommentStatus::class);
    
    $comments = Comment::query()
        ->withStatus($status)
        ->with(['user', 'post', 'approver'])
        ->latest()
        ->paginate(20);
    
    return view('admin.comments.index', [
        'comments' => $comments,
        'status' => $status,
    ]);
}
```

---

### 3. Single Comment Approval

**User Story**: An admin approves a single comment.

```php
// routes/web.php
Route::post('/admin/comments/{comment}/approve', [AdminCommentController::class, 'approve'])
    ->middleware(['auth', 'admin'])
    ->name('admin.comments.approve');

// app/Http/Controllers/AdminCommentController.php
public function approve(Comment $comment)
{
    if ($comment->approve(auth()->user())) {
        // Status changed - send notification
        $comment->user->notify(new CommentApprovedNotification($comment));
        
        return back()->with('success', 'Comment approved successfully!');
    }
    
    return back()->with('info', 'Comment was already approved.');
}
```

---

### 4. Bulk Comment Approval

**User Story**: An admin approves multiple comments at once.

```php
// routes/web.php
Route::post('/admin/comments/bulk-approve', [AdminCommentController::class, 'bulkApprove'])
    ->middleware(['auth', 'admin'])
    ->name('admin.comments.bulk-approve');

// app/Http/Controllers/AdminCommentController.php
public function bulkApprove(Request $request)
{
    $request->validate([
        'comment_ids' => 'required|array',
        'comment_ids.*' => 'exists:comments,id',
    ]);
    
    $admin = auth()->user();
    $approved = 0;
    
    foreach ($request->input('comment_ids') as $id) {
        $comment = Comment::find($id);
        
        if ($comment && $comment->approve($admin)) {
            $approved++;
            
            // Send notification
            $comment->user->notify(new CommentApprovedNotification($comment));
        }
    }
    
    return back()->with('success', "{$approved} comment(s) approved successfully!");
}
```

---

### 5. Comment Editing

**User Story**: A user edits their own comment.

```php
// routes/web.php
Route::put('/comments/{comment}', [CommentController::class, 'update'])
    ->middleware('auth')
    ->name('comments.update');

// app/Http/Controllers/CommentController.php
public function update(UpdateCommentRequest $request, Comment $comment)
{
    // Authorization check
    if (!$comment->canBeEditedBy(auth()->user())) {
        abort(403, 'You are not authorized to edit this comment.');
    }
    
    $comment->update([
        'content' => $request->validated('content'),
    ]);
    
    // Re-check for spam after edit
    if ($comment->isPotentialSpam()) {
        $comment->markPending();
        
        return back()->with('warning', 
            'Your edited comment has been flagged for review.'
        );
    }
    
    return back()->with('success', 'Comment updated successfully!');
}
```

---

### 6. Comment Deletion

**User Story**: A user or admin deletes a comment.

```php
// routes/web.php
Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
    ->middleware('auth')
    ->name('comments.destroy');

// app/Http/Controllers/CommentController.php
public function destroy(Comment $comment)
{
    // Authorization check
    if (!$comment->canBeDeletedBy(auth()->user())) {
        abort(403, 'You are not authorized to delete this comment.');
    }
    
    $comment->delete(); // Soft delete
    
    return back()->with('success', 'Comment deleted successfully!');
}
```

---

## Controller Examples

### RESTful Comment Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Notifications\CommentApprovedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    /**
     * Display a listing of the user's comments.
     */
    public function index(Request $request): View
    {
        $comments = Comment::byUser(auth()->id())
            ->with('post')
            ->latest()
            ->paginate(20);
        
        return view('comments.index', compact('comments'));
    }

    /**
     * Store a newly created comment.
     */
    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $comment = Comment::create([
            'user_id' => auth()->id(),
            'post_id' => $post->id,
            'content' => $request->validated('content'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($comment->isPotentialSpam()) {
            $comment->reject();
            
            return back()->with('warning', 
                'Your comment has been flagged for review.'
            );
        }

        return back()->with('success', 
            'Your comment has been submitted for moderation.'
        );
    }

    /**
     * Show the form for editing the comment.
     */
    public function edit(Comment $comment): View
    {
        if (!$comment->canBeEditedBy(auth()->user())) {
            abort(403);
        }
        
        return view('comments.edit', compact('comment'));
    }

    /**
     * Update the specified comment.
     */
    public function update(UpdateCommentRequest $request, Comment $comment): RedirectResponse
    {
        if (!$comment->canBeEditedBy(auth()->user())) {
            abort(403);
        }
        
        $comment->update([
            'content' => $request->validated('content'),
        ]);
        
        if ($comment->isPotentialSpam()) {
            $comment->markPending();
            
            return back()->with('warning', 
                'Your edited comment has been flagged for review.'
            );
        }
        
        return back()->with('success', 'Comment updated successfully!');
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        if (!$comment->canBeDeletedBy(auth()->user())) {
            abort(403);
        }
        
        $comment->delete();
        
        return back()->with('success', 'Comment deleted successfully!');
    }
}
```

---

## Livewire Component Examples

### Comment Moderation Component

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Comment;
use App\Enums\CommentStatus;
use App\Notifications\CommentApprovedNotification;
use Livewire\Component;
use Livewire\WithPagination;

class CommentModeration extends Component
{
    use WithPagination;

    public ?CommentStatus $statusFilter = null;
    public string $search = '';
    public array $selected = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function approve(int $commentId): void
    {
        $comment = Comment::findOrFail($commentId);
        
        if ($comment->approve(auth()->user())) {
            $comment->user->notify(new CommentApprovedNotification($comment));
            
            $this->dispatch('comment-approved', commentId: $commentId);
            session()->flash('success', 'Comment approved!');
        }
    }

    public function reject(int $commentId): void
    {
        $comment = Comment::findOrFail($commentId);
        
        if ($comment->reject()) {
            $this->dispatch('comment-rejected', commentId: $commentId);
            session()->flash('success', 'Comment rejected!');
        }
    }

    public function bulkApprove(): void
    {
        $admin = auth()->user();
        $approved = 0;
        
        foreach ($this->selected as $id) {
            $comment = Comment::find($id);
            
            if ($comment && $comment->approve($admin)) {
                $comment->user->notify(new CommentApprovedNotification($comment));
                $approved++;
            }
        }
        
        $this->selected = [];
        session()->flash('success', "{$approved} comment(s) approved!");
    }

    public function render()
    {
        $comments = Comment::query()
            ->withStatus($this->statusFilter)
            ->when($this->search, function ($query) {
                $query->where('content', 'like', "%{$this->search}%");
            })
            ->with(['user', 'post', 'approver'])
            ->latest()
            ->paginate(20);
        
        return view('livewire.admin.comment-moderation', [
            'comments' => $comments,
        ]);
    }
}
```

---

## Blade Template Examples

### Public Comment Display

```blade
{{-- resources/views/post/show.blade.php --}}

<div class="comments-section">
    <h2>Comments ({{ $post->comments()->approved()->count() }})</h2>
    
    @auth
        <form method="POST" action="{{ route('comments.store', $post) }}">
            @csrf
            
            <div class="form-group">
                <label for="content">Add a comment</label>
                <textarea 
                    name="content" 
                    id="content" 
                    rows="4" 
                    required
                    class="form-control"
                >{{ old('content') }}</textarea>
                
                @error('content')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            
            <button type="submit" class="btn btn-primary">
                Submit Comment
            </button>
        </form>
    @else
        <p>
            <a href="{{ route('login') }}">Log in</a> to leave a comment.
        </p>
    @endauth
    
    <div class="comments-list mt-4">
        @forelse($post->comments()->approved()->latest()->get() as $comment)
            <div class="comment" id="comment-{{ $comment->id }}">
                <div class="comment-header">
                    <strong>{{ $comment->user->name }}</strong>
                    <small class="text-muted">{{ $comment->formatted_date }}</small>
                </div>
                
                <div class="comment-body">
                    <p>{{ $comment->content }}</p>
                </div>
                
                @if($comment->canBeEditedBy(auth()->user()))
                    <div class="comment-actions">
                        <a href="{{ route('comments.edit', $comment) }}">Edit</a>
                        
                        @if($comment->canBeDeletedBy(auth()->user()))
                            <form 
                                method="POST" 
                                action="{{ route('comments.destroy', $comment) }}"
                                class="d-inline"
                                onsubmit="return confirm('Are you sure?')"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-link text-danger">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <p class="text-muted">No comments yet. Be the first to comment!</p>
        @endforelse
    </div>
</div>
```

---

### Admin Moderation Queue

```blade
{{-- resources/views/admin/comments/index.blade.php --}}

<x-layouts.admin>
    <x-slot name="header">
        <h1>Comment Moderation</h1>
    </x-slot>
    
    <div class="filters mb-4">
        <form method="GET" action="{{ route('admin.comments.index') }}">
            <select name="status" onchange="this.form.submit()">
                <option value="">All Comments</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>
                    Pending
                </option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>
                    Approved
                </option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>
                    Rejected
                </option>
            </select>
        </form>
    </div>
    
    <form method="POST" action="{{ route('admin.comments.bulk-approve') }}">
        @csrf
        
        <table class="table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>Author</th>
                    <th>Post</th>
                    <th>Content</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comments as $comment)
                    <tr class="{{ $comment->isPotentialSpam() ? 'table-warning' : '' }}">
                        <td>
                            <input 
                                type="checkbox" 
                                name="comment_ids[]" 
                                value="{{ $comment->id }}"
                                class="comment-checkbox"
                            >
                        </td>
                        <td>{{ $comment->user->name }}</td>
                        <td>
                            <a href="{{ route('posts.show', $comment->post) }}">
                                {{ Str::limit($comment->post->title, 30) }}
                            </a>
                        </td>
                        <td>
                            {{ Str::limit($comment->content, 50) }}
                            
                            @if($comment->isPotentialSpam())
                                <span class="badge badge-warning">Potential Spam</span>
                            @endif
                            
                            <br>
                            <small class="text-muted">IP: {{ $comment->masked_ip }}</small>
                        </td>
                        <td>
                            <x-ui.badge :status="$comment->status->value">
                                {{ $comment->status->label() }}
                            </x-ui.badge>
                        </td>
                        <td>{{ $comment->formatted_date }}</td>
                        <td>
                            @if($comment->isPending())
                                <form 
                                    method="POST" 
                                    action="{{ route('admin.comments.approve', $comment) }}"
                                    class="d-inline"
                                >
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">
                                        Approve
                                    </button>
                                </form>
                                
                                <form 
                                    method="POST" 
                                    action="{{ route('admin.comments.reject', $comment) }}"
                                    class="d-inline"
                                >
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Reject
                                    </button>
                                </form>
                            @endif
                            
                            <a 
                                href="{{ route('admin.comments.edit', $comment) }}"
                                class="btn btn-sm btn-secondary"
                            >
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            No comments found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($comments->isNotEmpty())
            <button type="submit" class="btn btn-primary">
                Approve Selected
            </button>
        @endif
    </form>
    
    {{ $comments->links() }}
    
    <script>
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('.comment-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    </script>
</x-layouts.admin>
```

---

## Event Handling

### Creating Events

```php
<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Comment $comment
    ) {}
}
```

### Dispatching Events

```php
use App\Events\CommentApproved;

if ($comment->approve(auth()->user())) {
    event(new CommentApproved($comment));
}
```

### Listening to Events

```php
<?php

namespace App\Listeners;

use App\Events\CommentApproved;
use App\Notifications\CommentApprovedNotification;

class SendCommentApprovedNotification
{
    public function handle(CommentApproved $event): void
    {
        $event->comment->user->notify(
            new CommentApprovedNotification($event->comment)
        );
    }
}
```

---

## Spam Prevention

### Automatic Spam Detection

```php
// app/Observers/CommentObserver.php
<?php

namespace App\Observers;

use App\Models\Comment;
use App\Notifications\SpamCommentDetected;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        if ($comment->isPotentialSpam()) {
            $comment->reject();
            
            // Notify moderators
            $admins = User::where('is_admin', true)->get();
            Notification::send($admins, new SpamCommentDetected($comment));
        }
    }
}
```

### Rate Limiting

```php
// app/Http/Middleware/CommentRateLimit.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CommentRateLimit
{
    public function handle(Request $request, Closure $next)
    {
        $key = 'comment-rate-limit:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return back()->with('error', 
                "Too many comments. Please try again in {$seconds} seconds."
            );
        }
        
        RateLimiter::hit($key, 60); // 5 comments per minute
        
        return $next($request);
    }
}
```

---

## Testing Strategies

### Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Tests\TestCase;

class CommentModerationTest extends TestCase
{
    public function test_admin_can_approve_comment(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $comment = Comment::factory()->create(['status' => CommentStatus::Pending]);
        
        $this->actingAs($admin)
            ->post(route('admin.comments.approve', $comment))
            ->assertRedirect()
            ->assertSessionHas('success');
        
        $this->assertTrue($comment->fresh()->isApproved());
        $this->assertEquals($admin->id, $comment->fresh()->approved_by);
    }
    
    public function test_non_admin_cannot_approve_comment(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $comment = Comment::factory()->create();
        
        $this->actingAs($user)
            ->post(route('admin.comments.approve', $comment))
            ->assertForbidden();
    }
}
```

### Unit Tests

```php
<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Enums\CommentStatus;
use Tests\TestCase;

class CommentSpamDetectionTest extends TestCase
{
    public function test_detects_excessive_links(): void
    {
        $comment = Comment::factory()->create([
            'content' => 'Check out http://spam1.com http://spam2.com http://spam3.com http://spam4.com',
        ]);
        
        $this->assertTrue($comment->isPotentialSpam());
    }
    
    public function test_detects_excessive_uppercase(): void
    {
        $comment = Comment::factory()->create([
            'content' => 'THIS IS ALL UPPERCASE SPAM MESSAGE',
        ]);
        
        $this->assertTrue($comment->isPotentialSpam());
    }
}
```

---

## Troubleshooting

### Issue: Comments not appearing

**Symptom**: Comments are submitted but don't appear on the post.

**Solution**: Check if comments are approved:
```php
// Instead of:
$comments = $post->comments;

// Use:
$comments = $post->comments()->approved()->get();
```

---

### Issue: N+1 query problem

**Symptom**: Slow page load with many comments.

**Solution**: Eager load relationships:
```php
// Instead of:
$comments = Comment::approved()->get();

// Use:
$comments = Comment::with(['user', 'post'])->approved()->get();
```

---

### Issue: Soft deleted comments appearing

**Symptom**: Deleted comments still showing up.

**Solution**: Soft deletes are automatic, but check your queries:
```php
// This excludes soft deleted:
$comments = Comment::approved()->get();

// This includes soft deleted:
$comments = Comment::withTrashed()->approved()->get();
```

---

## Best Practices

1. **Always use scopes** for status filtering
2. **Eager load relationships** to avoid N+1 queries
3. **Use permission methods** instead of inline checks
4. **Implement spam detection** on comment creation
5. **Track approvers** for accountability
6. **Paginate results** for large datasets
7. **Use events** for side effects (notifications, logging)
8. **Test idempotency** of status transitions

---

## Related Documentation

- **API Reference**: `COMMENT_MODEL_API.md`
- **Quick Reference**: `COMMENT_MODEL_QUICK_REFERENCE.md`
- **Architecture**: `COMMENT_MODEL_ARCHITECTURE.md`

---

**Need Help?** Check the troubleshooting section or create an issue on GitHub.
