<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Livewire\Volt\Volt;
use function Pest\Laravel\actingAs;

it('creates users from the admin modal and validates unique emails', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(['email' => 'taken@example.com']);

    actingAs($admin);

    Volt::test('admin.users.index')
        ->set('createForm.name', 'Duplicate User')
        ->set('createForm.email', 'taken@example.com')
        ->set('createForm.password', 'password123')
        ->set('createForm.password_confirmation', 'password123')
        ->call('createUser')
        ->assertHasErrors(['createForm.email' => 'unique']);

    Volt::test('admin.users.index')
        ->set('createForm.name', 'Fresh Author')
        ->set('createForm.email', 'writer@example.com')
        ->set('createForm.password', 'password123')
        ->set('createForm.password_confirmation', 'password123')
        ->set('createForm.is_author', true)
        ->call('createUser')
        ->assertSee('Fresh Author');

    $created = User::whereEmail('writer@example.com')->firstOrFail();

    expect($created->is_author)->toBeTrue()
        ->and($created->is_admin)->toBeFalse()
        ->and($created->is_banned)->toBeFalse();
});

it('filters users by search term', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(['name' => 'Marisol Editor']);
    User::factory()->create(['name' => 'John Writer']);

    actingAs($admin);

    Volt::test('admin.users.index')
        ->set('search', 'Marisol')
        ->assertSee('Marisol Editor')
        ->assertDontSee('John Writer');
});

it('updates user roles and ban status from the admin table', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'is_author' => false,
        'is_admin' => false,
        'is_banned' => false,
    ]);

    actingAs($admin);

    Volt::test('admin.users.index')
        ->call('toggleAuthor', $user->id)
        ->call('toggleBan', $user->id)
        ->call('toggleBan', $user->id)
        ->call('toggleAdmin', $user->id);

    $user->refresh();

    expect($user->is_author)->toBeTrue()
        ->and($user->is_banned)->toBeFalse()
        ->and($user->is_admin)->toBeTrue();
});

it('reassigns posts and deletes comments when removing a user', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $author = User::factory()->create(['name' => 'Departing Author', 'is_author' => true]);

    $posts = Post::factory()
        ->count(2)
        ->for($author, 'author')
        ->create();

    Comment::factory()->create([
        'user_id' => $author->id,
        'post_id' => $posts->first()->id,
    ]);

    actingAs($admin);

    Volt::test('admin.users.index')
        ->call('confirmDelete', $author->id)
        ->set('deleteStrategy', 'transfer')
        ->set('transferTarget', $admin->id)
        ->call('deleteUser');

    expect(User::find($author->id))->toBeNull();
    expect(Post::withoutGlobalScopes()->where('user_id', $admin->id)->count())->toBeGreaterThanOrEqual(2);
    expect(Comment::where('user_id', $author->id)->exists())->toBeFalse();
});
