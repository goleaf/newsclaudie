<?php

declare(strict_types=1);

use App\Models\User;
test('admin can navigate the Flux dashboard links', function (): void {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->press(__('Log in'))
        ->waitForEvent('networkidle');

    $this->visit('/admin/dashboard')
        ->assertSee(__('admin.dashboard.heading'));

    $this->visit('/admin/posts')
        ->assertSee(__('admin.posts.heading'));

    $this->visit('/admin/categories')
        ->assertSee(__('admin.categories.heading'));

    $this->visit('/admin/comments')
        ->assertSee(__('admin.comments.heading'));

    $this->visit('/admin/users')
        ->assertSee(__('admin.users.heading'));
});

