<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;

test('admin can publish and unpublish posts via Volt table actions', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $post = Post::factory()->create([
        'title' => 'Browser Publish Demo',
        'published_at' => null,
    ]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->press(__('Log in'))
        ->waitForEvent('networkidle');

    $this->visit('/admin/posts')
        ->assertSee($post->title)
        ->press(__('admin.posts.action_publish'))
        ->waitForEvent('networkidle')
        ->assertSee(__('admin.posts.status.published'));

    $this->visit('/admin/posts')
        ->press(__('admin.posts.action_unpublish'))
        ->waitForEvent('networkidle')
        ->assertSee(__('admin.posts.status.draft'));
});

