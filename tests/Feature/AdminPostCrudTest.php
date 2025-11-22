<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;
use function Pest\Laravel\actingAs;

it('redirects admins back to the admin index after creating a post', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $payload = [
        'title' => 'Admin Created Post',
        'body' => 'Admin body content',
        'description' => 'Admin description',
        'featured_image' => 'https://example.com/image.jpg',
        'published_at' => now()->format('Y-m-d\TH:i'),
        'redirect_to' => 'admin.posts.index',
    ];

    actingAs($admin)
        ->post(route('posts.store'), $payload)
        ->assertRedirect(route('admin.posts.index'));

    expect(
        Post::withoutGlobalScopes()->where('title', 'Admin Created Post')->exists()
    )->toBeTrue();
});

it('redirects admin post updates back to the index when requested', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $post = Post::factory()->create([
        'user_id' => $admin->id,
        'published_at' => now(),
    ]);

    actingAs($admin)
        ->patch(route('posts.update', $post), [
            'title' => 'Updated Admin Post',
            'body' => 'Updated body',
            'description' => 'Updated description',
            'featured_image' => 'https://example.com/updated.jpg',
            'published_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'redirect_to' => 'admin.posts.index',
        ])
        ->assertRedirect(route('admin.posts.index'));

    expect($post->fresh()->title)->toBe('Updated Admin Post');
});

it('deletes posts from admin routes and returns to the index', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $post = Post::factory()->create([
        'user_id' => $admin->id,
        'published_at' => now(),
    ]);

    actingAs($admin)
        ->delete(route('posts.destroy', $post), ['redirect_to' => 'admin.posts.index'])
        ->assertRedirect(route('admin.posts.index'));

    expect(Post::withoutGlobalScopes()->find($post->id))->toBeNull();
});
