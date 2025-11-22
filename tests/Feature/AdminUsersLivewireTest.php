<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

it('creates a user from the Livewire modal with role toggles', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    Volt::actingAs($admin)
        ->test('admin.users.index')
        ->set('createForm.name', 'Copy Desk')
        ->set('createForm.email', 'copy@example.com')
        ->set('createForm.password', 'CopyDesk123!')
        ->set('createForm.password_confirmation', 'CopyDesk123!')
        ->set('createForm.is_admin', true)
        ->set('createForm.is_author', true)
        ->call('createUser')
        ->assertHasNoErrors()
        ->assertSee(__('admin.users.created', ['name' => 'Copy Desk']));

    $created = User::query()->where('email', 'copy@example.com')->first();

    expect($created)->not->toBeNull();
    expect($created?->is_admin)->toBeTrue();
    expect($created?->is_author)->toBeTrue();
    expect(Hash::check('CopyDesk123!', (string) $created?->password))->toBeTrue();
});

it('enforces unique emails when creating users', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $existing = User::factory()->create(['email' => 'writer@example.com']);

    Volt::actingAs($admin)
        ->test('admin.users.index')
        ->set('createForm.name', 'Another User')
        ->set('createForm.email', $existing->email)
        ->set('createForm.password', 'UniqueTest123!')
        ->set('createForm.password_confirmation', 'UniqueTest123!')
        ->call('createUser')
        ->assertHasErrors(['createForm.email' => 'unique']);
});

it('filters users by search term in real time', function (): void {
    $admin = User::factory()->create(['name' => 'Admin User', 'is_admin' => true]);
    $alpha = User::factory()->create(['name' => 'Alpha Reporter']);
    $beta = User::factory()->create(['name' => 'Beta Editor']);

    Volt::actingAs($admin)
        ->test('admin.users.index')
        ->assertSee($alpha->name)
        ->assertSee($beta->name)
        ->set('search', 'Alpha')
        ->assertSee($alpha->name)
        ->assertDontSee($beta->name);
});

it('transfers posts when deleting a user with the transfer strategy', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $author = User::factory()->create(['is_author' => true]);

    $post = Post::factory()->for($author, 'author')->create();
    $comment = Comment::factory()->create([
        'user_id' => $author->id,
        'post_id' => $post->id,
    ]);

    $component = Volt::actingAs($admin)->test('admin.users.index');

    $component->call('confirmDelete', $author->id);
    $component->set('deleteStrategy', 'transfer');
    $component->set('transferTarget', $admin->id);
    $component->call('deleteUser')
        ->assertSee(__('admin.users.deleted', ['name' => $author->name]));

    expect(User::query()->whereKey($author->id)->exists())->toBeFalse();
    expect(Post::withoutGlobalScopes()->find($post->id)?->user_id)->toBe($admin->id);
    expect(Comment::query()->whereKey($comment->id)->exists())->toBeFalse();
});

it('bans and unbans users via the toggle', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    $component = Volt::actingAs($admin)->test('admin.users.index');

    $component->call('toggleBan', $user->id)
        ->assertSee(__('admin.users.banned', ['name' => $user->name]));

    expect($user->fresh()->is_banned)->toBeTrue();

    $component->call('toggleBan', $user->id);

    expect($user->fresh()->is_banned)->toBeFalse();
});
