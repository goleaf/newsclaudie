# Comment Inline Edit Property Testing - Quick Reference

**Test File**: `tests/Unit/CommentInlineEditPropertyTest.php`  
**Feature**: admin-livewire-crud  
**Property**: Property 1 - Data persistence round-trip (inline edit aspect)  
**Validates**: Requirements 3.3

## Quick Commands

```bash
# Run all inline edit tests
php artisan test tests/Unit/CommentInlineEditPropertyTest.php

# Run specific test
php artisan test --filter=inline_edit_content_persists_correctly

# Run with verbose output
php artisan test tests/Unit/CommentInlineEditPropertyTest.php --verbose

# Run in parallel
php artisan test tests/Unit/CommentInlineEditPropertyTest.php --parallel

# Run all property tests
php artisan test --group=property-testing

# Run all admin CRUD tests
php artisan test --group=admin-livewire-crud
```

## Test Summary

| Test | Iterations | Assertions | Purpose |
|------|-----------|-----------|---------|
| Content persistence | 100 | ~300 | Verify content updates persist |
| Status persistence | 100 | ~200 | Verify status updates persist |
| Sequential edits | 50 × 3 | ~300 | Verify multiple edits work |
| Empty content | 50 | ~100 | Verify empty content handling |
| Timestamps | 100 | ~200 | Verify timestamp management |

**Total**: 5 tests, ~1,100 assertions, ~1.7s duration

## Property Definition

**Property 1: Data persistence round-trip (inline edit aspect)**

> For any comment and any valid content or status value, updating the comment via inline edit should persist the changes to the database and return the updated values on retrieval.

## Key Test Patterns

### Basic Inline Edit Pattern

```php
// Create comment
$comment = Comment::factory()->create([
    'content' => 'Original content',
    'status' => CommentStatus::Pending,
]);

// Perform inline edit
$comment->forceFill([
    'content' => trim('New content'),
    'status' => CommentStatus::Approved,
])->save();

// Verify persistence
$comment->refresh();
expect($comment->content)->toBe('New content');
expect($comment->status)->toBe(CommentStatus::Approved);
```

### Sequential Edits Pattern

```php
$comment = Comment::factory()->create();

for ($j = 0; $j < 3; $j++) {
    $newContent = generateRandomContent();
    $newStatus = generateRandomStatus();
    
    $comment->forceFill([
        'content' => trim($newContent),
        'status' => $newStatus,
    ])->save();
    
    $comment->refresh();
    expect($comment->content)->toBe(trim($newContent));
    expect($comment->status)->toBe($newStatus);
}
```

### Timestamp Verification Pattern

```php
$originalCreatedAt = $comment->created_at;
$originalUpdatedAt = $comment->updated_at;

$this->travel(1)->seconds();

$comment->forceFill(['content' => 'New content'])->save();

$comment->refresh();
expect($comment->created_at->equalTo($originalCreatedAt))->toBeTrue();
expect($comment->updated_at->greaterThan($originalUpdatedAt))->toBeTrue();
```

## Helper Functions

```php
// Generate random content
function generateRandomContent(): string
{
    $templates = [
        'This is a test comment with random content.',
        'Great article! Thanks for sharing.',
        // ... more templates
    ];
    return $templates[array_rand($templates)] . ' ' . bin2hex(random_bytes(8));
}

// Generate random status
function generateRandomStatus(): CommentStatus
{
    $statuses = CommentStatus::cases();
    return $statuses[array_rand($statuses)];
}

// Generate different status
function generateDifferentStatus(CommentStatus $current): CommentStatus
{
    $statuses = array_filter(
        CommentStatus::cases(),
        fn($status) => $status !== $current
    );
    return $statuses[array_rand($statuses)];
}
```

## Common Assertions

```php
// Content persistence
expect($comment->content)->toBe(trim($newContent));
expect($comment->content)->not->toBe($originalContent);

// Status persistence
expect($comment->status)->toBe($newStatus);
expect($comment->status)->not->toBe($originalStatus);

// Empty content
expect($comment->content)->toBe('');

// Timestamps
expect($comment->created_at->equalTo($originalCreatedAt))->toBeTrue();
expect($comment->updated_at->greaterThan($originalUpdatedAt))->toBeTrue();
```

## Troubleshooting

### Content not persisting
```php
// Check: Using forceFill() and save()
$comment->forceFill(['content' => trim($newContent)])->save();

// Check: Database column length
Schema::table('comments', function (Blueprint $table) {
    $table->text('content')->change();  // Not string(255)
});
```

### Status not persisting
```php
// Check: Enum cast in model
protected $casts = [
    'status' => CommentStatus::class,
];

// Check: Using enum value
$comment->forceFill(['status' => CommentStatus::Approved])->save();
```

### Timestamps not updating
```php
// Check: Timestamps enabled
public $timestamps = true;  // Default

// Check: Using time travel
$this->travel(1)->seconds();

// Check: Calling save()
$comment->forceFill(['content' => 'New'])->save();  // Not update()
```

### Empty content fails
```php
// Check: Using forceFill() to bypass validation
$comment->forceFill(['content' => ''])->save();

// Note: Form validation should prevent empty content
// This test verifies model-level behavior only
```

## Integration Examples

### Livewire Component

```php
public function saveInlineEdit($commentId, $content, $status)
{
    $comment = Comment::findOrFail($commentId);
    $this->authorize('update', $comment);
    
    $this->validate([
        'content' => 'required|string|max:1000',
        'status' => 'required|in:pending,approved,rejected',
    ]);
    
    $comment->forceFill([
        'content' => trim($content),
        'status' => CommentStatus::from($status),
    ])->save();
    
    session()->flash('message', 'Comment updated successfully.');
}
```

### Controller

```php
public function update(UpdateCommentRequest $request, Comment $comment)
{
    $this->authorize('update', $comment);
    
    $comment->forceFill([
        'content' => trim($request->input('content')),
        'status' => CommentStatus::from($request->input('status')),
    ])->save();
    
    return redirect()->back()->with('success', 'Comment updated.');
}
```

## Expected Test Output

```
PASS  Tests\Unit\CommentInlineEditPropertyTest
✓ inline edit content persists correctly         0.35s
✓ inline edit status persists correctly          0.32s
✓ multiple sequential inline edits persist correctly  0.45s
✓ inline edit handles empty content              0.18s
✓ inline edit updates timestamps correctly       0.40s

Tests:    5 passed (1100 assertions)
Duration: 1.70s
```

## Related Documentation

- [Full Testing Guide](COMMENT_INLINE_EDIT_PROPERTY_TESTING.md)
- [Comment Status Filter Testing](COMMENT_STATUS_FILTER_TESTING.md)
- [Property Testing Guide](../PROPERTY_TESTING.md)
- [Test Coverage](../../docs/TEST_COVERAGE.md)

## Quick Tips

1. ✅ Use `forceFill()` to bypass fillable restrictions
2. ✅ Always `trim()` content before saving
3. ✅ Use `refresh()` to reload from database
4. ✅ Use `travel()` for timestamp tests
5. ✅ Clean up test data after each iteration
6. ✅ Use enum values for status, not strings
7. ✅ Test both content and status together
8. ✅ Verify original values are replaced, not appended
