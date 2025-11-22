<?php

declare(strict_types=1);

use App\Models\Post;

test('renders the homepage', function (): void {
    Post::factory()->create(['published_at' => now()]);

    visit('/')
        ->assertSee(config('app.name'))
        ->assertSee(__('home.latest_heading'));
});
