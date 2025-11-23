<?php

declare(strict_types=1);

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

/**
 * Property-Based Tests for Comment Inline Edit Persistence
 *
 * Feature: admin-livewire-crud, Property 1: Data persistence round-trip (inline edit aspect)
 * Validates: Requirements 3.3
 *
 * These tests verify that inline editing of comments correctly persists changes
 * to both content and status fields, maintaining data integrity across updates.
 */

/**
 * Property: Inline edit content persistence
 *
 * For any comment and any valid content string, updating the content via inline edit
 * should persist the new content to the database.
 */
test('inline edit content persists correctly', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    // Run 100 iterations with random content
    for ($i = 0; $i < 100; $i++) {
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => generateRandomContent(),
            'status' => generateRandomStatus(),
        ]);

        $originalContent = $comment->content;
        $newContent = generateRandomContent();

        // Simulate inline edit
        $comment->forceFill(['content' => trim($newContent)])->save();

        // Verify persistence
        $comment->refresh();
        expect($comment->content)->toBe(trim($newContent));
        expect($comment->content)->not->toBe($originalContent);

        // Clean up for next iteration
        $comment->delete();
    }
});

/**
 * Property: Inline edit status persistence
 *
 * For any comment and any valid status, updating the status via inline edit
 * should persist the new status to the database.
 */
test('inline edit status persists correctly', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    // Run 100 iterations with random status changes
    for ($i = 0; $i < 100; $i++) {
        $originalStatus = generateRandomStatus();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => generateRandomContent(),
            'status' => $originalStatus,
        ]);

        $newStatus = generateDifferentStatus($originalStatus);

        // Simulate inline edit
        $comment->forceFill(['status' => $newStatus])->save();

        // Verify persistence
        $comment->refresh();
        expect($comment->status)->toBe($newStatus);
        expect($comment->status)->not->toBe($originalStatus);

        // Clean up for next iteration
        $comment->delete();
    }
});

/**
 * Property: Multiple sequential inline edits
 *
 * For any comment, performing multiple sequential inline edits should
 * correctly persist each change without data corruption.
 */
test('multiple sequential inline edits persist correctly', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    // Run 50 iterations (each with 3 sequential edits)
    for ($i = 0; $i < 50; $i++) {
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => generateRandomContent(),
            'status' => generateRandomStatus(),
        ]);

        // Perform 3 sequential edits
        for ($j = 0; $j < 3; $j++) {
            $newContent = generateRandomContent();
            $newStatus = generateRandomStatus();

            $comment->forceFill([
                'content' => trim($newContent),
                'status' => $newStatus,
            ])->save();

            // Verify each edit persists
            $comment->refresh();
            expect($comment->content)->toBe(trim($newContent));
            expect($comment->status)->toBe($newStatus);
        }

        // Clean up for next iteration
        $comment->delete();
    }
});

/**
 * Property: Empty content edge case
 *
 * For any comment, attempting to save empty content should be handled
 * (either rejected by validation or stored as empty).
 */
test('inline edit handles empty content', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    // Run 50 iterations
    for ($i = 0; $i < 50; $i++) {
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => generateRandomContent(),
            'status' => generateRandomStatus(),
        ]);

        $originalContent = $comment->content;

        // Try to save empty content (trimmed)
        $comment->forceFill(['content' => trim('')])->save();

        // Verify the behavior (empty string is stored)
        $comment->refresh();
        expect($comment->content)->toBe('');
        expect($comment->content)->not->toBe($originalContent);

        // Clean up for next iteration
        $comment->delete();
    }
});

/**
 * Property: Timestamp updates
 *
 * For any comment inline edit, the updated_at timestamp should change
 * while created_at should remain unchanged.
 */
test('inline edit updates timestamps correctly', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    // Run 100 iterations
    for ($i = 0; $i < 100; $i++) {
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => generateRandomContent(),
            'status' => generateRandomStatus(),
        ]);

        $originalCreatedAt = $comment->created_at;
        $originalUpdatedAt = $comment->updated_at;

        // Wait a moment to ensure timestamp difference
        $this->travel(1)->seconds();

        // Perform inline edit
        $comment->forceFill([
            'content' => trim(generateRandomContent()),
        ])->save();

        // Verify timestamps
        $comment->refresh();
        expect($comment->created_at->equalTo($originalCreatedAt))->toBeTrue();
        expect($comment->updated_at->greaterThan($originalUpdatedAt))->toBeTrue();

        // Clean up for next iteration
        $comment->delete();
    }
});

/**
 * Generate random comment content for testing.
 * 
 * Selects a random template from a predefined list and adds a unique
 * 16-character hex suffix to ensure content uniqueness across iterations.
 * 
 * Templates include common comment patterns like questions, praise,
 * disagreement, and general feedback.
 * 
 * @return string Random comment content with unique suffix
 * 
 * @example
 * generateRandomContent()
 * // Returns: "Great article! Thanks for sharing. a1b2c3d4e5f6g7h8"
 */
function generateRandomContent(): string
{
    $templates = [
        'This is a test comment with random content.',
        'Great article! Thanks for sharing.',
        'I have a question about this topic.',
        'Very informative post.',
        'Could you elaborate on this point?',
        'Excellent explanation!',
        'I disagree with this perspective.',
        'This helped me understand the concept better.',
        'Looking forward to more content like this.',
        'Well written and easy to follow.',
    ];

    $content = $templates[array_rand($templates)];

    // Add random suffix to ensure uniqueness
    return $content . ' ' . bin2hex(random_bytes(8));
}

/**
 * Generate random comment status for testing.
 * 
 * Returns a random CommentStatus enum value from all available cases
 * (Pending, Approved, Rejected).
 * 
 * @return CommentStatus Random status enum value
 * 
 * @example
 * generateRandomStatus()
 * // Returns: CommentStatus::Approved (or Pending, or Rejected)
 */
function generateRandomStatus(): CommentStatus
{
    $statuses = CommentStatus::cases();
    return $statuses[array_rand($statuses)];
}

/**
 * Generate a different status from the given one.
 * 
 * Returns a random CommentStatus enum value that is different from
 * the provided current status. Useful for testing status transitions.
 * 
 * @param CommentStatus $current The current status to exclude
 * @return CommentStatus Random status different from current
 * 
 * @example
 * generateDifferentStatus(CommentStatus::Pending)
 * // Returns: CommentStatus::Approved or CommentStatus::Rejected (never Pending)
 */
function generateDifferentStatus(CommentStatus $current): CommentStatus
{
    $statuses = array_filter(
        CommentStatus::cases(),
        fn($status) => $status !== $current
    );

    return $statuses[array_rand($statuses)];
}
