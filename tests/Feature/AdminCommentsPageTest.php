<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
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

