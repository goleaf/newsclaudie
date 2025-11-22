<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Enums\CommentStatus;
use Livewire\Volt\Volt;
use function Pest\Laravel\actingAs;

it('renders the admin comments page for administrators', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create();

    $comment = Comment::factory()
        ->for($post)
        ->for($author, 'user')
        ->create();

    actingAs($admin)
        ->get(route('admin.comments.index'))
        ->assertOk()
        ->assertSeeText(__('admin.comments.heading'))
        ->assertSeeText(e($comment->content));
});

it('forbids non-admins from visiting the admin comments page', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('admin.comments.index'))
        ->assertForbidden();
});

it('filters comments by status', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create();

    $approved = Comment::factory()
        ->for($post)
        ->for($author, 'user')
        ->approved()
        ->create(['content' => 'Approved body']);

    $pending = Comment::factory()
        ->for($post)
        ->for($author, 'user')
        ->create(['content' => 'Pending body']);

    actingAs($admin);

    Volt::test('admin.comments.index')
        ->set('status', CommentStatus::Approved->value)
        ->assertSeeText($approved->content)
        ->assertDontSeeText($pending->content);
});

it('saves inline comment edits and status changes', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create();

    $comment = Comment::factory()
        ->for($post)
        ->for($author, 'user')
        ->create([
            'content' => 'Original',
            'status' => CommentStatus::Pending,
        ]);

    actingAs($admin);

    Volt::test('admin.comments.index')
        ->call('startEditing', $comment->id)
        ->set('editingContent', 'Updated inline content')
        ->set('editingStatus', CommentStatus::Approved->value)
        ->call('updateComment')
        ->assertSet('editingCommentId', null)
        ->assertSeeText('Updated inline content');

    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'content' => 'Updated inline content',
        'status' => CommentStatus::Approved->value,
    ]);
});

it('applies bulk approval and deletion and refreshes post counts', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create();

    $first = Comment::factory()->for($post)->for($author, 'user')->create(['status' => CommentStatus::Pending]);
    $second = Comment::factory()->for($post)->for($author, 'user')->create(['status' => CommentStatus::Pending]);
    $third = Comment::factory()->for($post)->for($author, 'user')->approved()->create();

    actingAs($admin);

    Volt::test('admin.comments.index')
        ->set('selected', [$first->id, $second->id])
        ->call('bulkApprove')
        ->assertSet('selected', [])
        ->set('selected', [$first->id])
        ->call('bulkDelete')
        ->assertSet('selected', []);

    $this->assertDatabaseHas('comments', ['id' => $second->id, 'status' => CommentStatus::Approved->value]);
    $this->assertDatabaseMissing('comments', ['id' => $first->id]);

    $updatedPost = Post::query()
        ->withCount(['comments as comments_count' => fn ($query) => $query->approved()])
        ->findOrFail($post->id);

    expect($updatedPost->comments_count)->toBe(2);
});
