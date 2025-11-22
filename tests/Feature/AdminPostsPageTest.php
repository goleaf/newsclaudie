<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;
use function Pest\Laravel\actingAs;

it('renders the admin posts page for administrators', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $posts = Post::factory()
        ->count(2)
        ->create(['user_id' => $admin->id, 'published_at' => now()]);

    actingAs($admin)
        ->get(route('admin.posts.index'))
        ->assertOk()
        ->assertSeeText(__('admin.posts.heading'))
        ->assertSeeText(e($posts->first()->title))
        ->assertSeeText(__('admin.posts.status.published'));
});

it('forbids non-admins from visiting the admin posts page', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('admin.posts.index'))
        ->assertForbidden();
});

