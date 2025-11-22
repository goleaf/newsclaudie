<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Test that format hints are displayed in admin forms
 * 
 * **Feature: admin-livewire-crud, Task 11.1**
 * Validates: Requirements 10.3
 */
class AdminFormValidationHintsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);
    }

    public function test_category_form_displays_slug_format_hint(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.categories.category-form')
            ->call('open')
            ->assertSee('Use lowercase letters, numbers, and hyphens only');
    }

    public function test_category_form_hides_hint_when_error_present(): void
    {
        $this->actingAs($this->admin);

        $existingCategory = Category::factory()->create(['slug' => 'existing-slug']);

        Livewire::test('admin.categories.category-form')
            ->call('open')
            ->set('name', 'Test Category')
            ->set('slug', 'existing-slug')
            ->call('save')
            ->assertHasErrors(['slug' => 'unique'])
            ->assertDontSee('Use lowercase letters, numbers, and hyphens only');
    }

    public function test_post_form_displays_slug_format_hint(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.posts.index')
            ->call('openCreateModal')
            ->assertSee('Use lowercase letters, numbers, and hyphens only');
    }

    public function test_user_form_displays_email_format_hint(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.users.index')
            ->call('openCreateModal')
            ->assertSee('Must be a valid email address');
    }

    public function test_user_form_displays_password_format_hint(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.users.index')
            ->call('openCreateModal')
            ->assertSee('Minimum 8 characters required');
    }

    public function test_user_form_hides_email_hint_when_error_present(): void
    {
        $this->actingAs($this->admin);

        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test('admin.users.index')
            ->call('openCreateModal')
            ->set('createForm.name', 'Test User')
            ->set('createForm.email', 'existing@example.com')
            ->set('createForm.password', 'password123')
            ->set('createForm.password_confirmation', 'password123')
            ->call('createUser')
            ->assertHasErrors(['createForm.email' => 'unique'])
            ->assertDontSee('Must be a valid email address');
    }

    public function test_format_hints_provide_helpful_examples(): void
    {
        $this->actingAs($this->admin);

        // Category slug hint
        Livewire::test('admin.categories.category-form')
            ->call('open')
            ->assertSee('my-category-name');

        // Post slug hint
        Livewire::test('admin.posts.index')
            ->call('openCreateModal')
            ->assertSee('my-post-title');

        // Email hint
        Livewire::test('admin.users.index')
            ->call('openCreateModal')
            ->assertSee('user@example.com');
    }
}
