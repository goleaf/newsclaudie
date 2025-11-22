<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Helpers\PropertyTesting;
use Tests\TestCase;

/**
 * Feature: admin-livewire-crud, Property 20: Bulk operation partial failure reporting
 * 
 * For any bulk action where some items fail to process, the system should display 
 * which items failed and the reason for each failure.
 * 
 * Validates: Requirements 8.5
 */
final class BulkPartialFailurePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 20: Bulk operation partial failure reporting
     * 
     * This test verifies that when a bulk action partially fails:
     * 1. The system tracks which items failed
     * 2. Each failure includes the item ID
     * 3. Each failure includes a reason for the failure
     * 4. The system reports both successful and failed counts
     * 5. Failed items remain selected for retry
     */
    public function test_bulk_action_reports_partial_failures_with_details(): void
    {
        PropertyTesting::run(function ($faker) {
            // Create an admin user for authorization
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);

            // Generate random number of posts (between 5 and 20)
            $totalPosts = $faker->numberBetween(5, 20);
            
            // Create posts - some will exist, some won't
            // Make them unpublished so bulk publish can actually update them
            $existingPosts = Post::factory()
                ->count($faker->numberBetween(3, $totalPosts - 2))
                ->create(['published_at' => null]);

            // Create a mix of valid and invalid IDs
            $validIds = $existingPosts->pluck('id')->toArray();
            $invalidIds = collect(range($existingPosts->max('id') + 1, $existingPosts->max('id') + 5))
                ->random($faker->numberBetween(1, 3))
                ->toArray();
            
            $allSelectedIds = array_merge($validIds, $invalidIds);
            shuffle($allSelectedIds);

            // Test the Livewire component directly
            $component = Livewire::test('admin.posts.index')
                ->set('selected', $allSelectedIds)
                ->call('bulkPublish');

            // Get the bulkFeedback property from the component
            $feedback = $component->get('bulkFeedback');

            // Verify the feedback structure contains all required fields
            $this->assertArrayHasKey('attempted', $feedback, 'Feedback must include attempted count');
            $this->assertArrayHasKey('updated', $feedback, 'Feedback must include updated count');
            $this->assertArrayHasKey('failures', $feedback, 'Feedback must include failures array');

            // Verify attempted count matches total selected
            $this->assertEquals(
                count($allSelectedIds),
                $feedback['attempted'],
                'Attempted count must equal total selected items'
            );

            // Verify failures array contains entries for invalid IDs
            $this->assertIsArray($feedback['failures'], 'Failures must be an array');
            $this->assertGreaterThan(0, count($feedback['failures']), 'Should have at least one failure');

            // Verify each failure has required fields
            foreach ($feedback['failures'] as $failure) {
                $this->assertArrayHasKey('id', $failure, 'Each failure must include item ID');
                $this->assertArrayHasKey('reason', $failure, 'Each failure must include failure reason');
                $this->assertNotEmpty($failure['reason'], 'Failure reason must not be empty');
                
                // Verify the failed ID was in the invalid set
                $this->assertContains(
                    $failure['id'],
                    $invalidIds,
                    'Failed ID should be one of the invalid IDs'
                );
            }

            // Verify success count is correct
            $expectedSuccessCount = count($validIds);
            $this->assertEquals(
                $expectedSuccessCount,
                $feedback['updated'],
                'Updated count should equal number of valid IDs'
            );

            // Verify the sum of successes and failures equals attempted
            $this->assertEquals(
                $feedback['attempted'],
                $feedback['updated'] + count($feedback['failures']),
                'Sum of updated and failures must equal attempted'
            );
        }, 100);
    }

    /**
     * Property 20: Bulk operation partial failure reporting (Comments variant)
     * 
     * Test the same property for comment bulk actions to ensure consistency
     * across different resource types.
     */
    public function test_bulk_comment_action_reports_partial_failures_with_details(): void
    {
        PropertyTesting::run(function ($faker) {
            // Create an admin user for authorization
            $admin = User::factory()->create(['is_admin' => true]);
            $this->actingAs($admin);

            // Create a post to associate comments with
            $post = Post::factory()->create();

            // Generate random number of comments (between 5 and 20)
            $totalComments = $faker->numberBetween(5, 20);
            
            // Create comments - some will exist, some won't
            $existingComments = Comment::factory()
                ->count($faker->numberBetween(3, $totalComments - 2))
                ->for($post)
                ->create(['status' => CommentStatus::Pending]);

            // Create a mix of valid and invalid IDs
            $validIds = $existingComments->pluck('id')->toArray();
            $invalidIds = collect(range($existingComments->max('id') + 1, $existingComments->max('id') + 5))
                ->random($faker->numberBetween(1, 3))
                ->toArray();
            
            $allSelectedIds = array_merge($validIds, $invalidIds);
            shuffle($allSelectedIds);

            // Test the Livewire component directly
            $component = Livewire::test('admin.comments.index')
                ->set('selected', $allSelectedIds)
                ->call('bulkApprove');

            // Get the bulkFeedback property from the component
            $feedback = $component->get('bulkFeedback');

            // Verify the feedback structure contains all required fields
            $this->assertArrayHasKey('total', $feedback, 'Feedback must include total count');
            $this->assertArrayHasKey('updated', $feedback, 'Feedback must include updated count');
            $this->assertArrayHasKey('failures', $feedback, 'Feedback must include failures array');

            // Verify total count matches total selected
            $this->assertEquals(
                count($allSelectedIds),
                $feedback['total'],
                'Total count must equal total selected items'
            );

            // Verify failures array contains entries for invalid IDs
            $this->assertIsArray($feedback['failures'], 'Failures must be an array');
            $this->assertGreaterThan(0, count($feedback['failures']), 'Should have at least one failure');

            // Verify each failure has required fields
            foreach ($feedback['failures'] as $failure) {
                $this->assertArrayHasKey('id', $failure, 'Each failure must include item ID');
                $this->assertArrayHasKey('reason', $failure, 'Each failure must include failure reason');
                $this->assertNotEmpty($failure['reason'], 'Failure reason must not be empty');
                
                // Verify the failed ID was in the invalid set
                $this->assertContains(
                    $failure['id'],
                    $invalidIds,
                    'Failed ID should be one of the invalid IDs'
                );
            }

            // Verify success count is correct
            $expectedSuccessCount = count($validIds);
            $this->assertEquals(
                $expectedSuccessCount,
                $feedback['updated'],
                'Updated count should equal number of valid IDs'
            );

            // Verify the sum of successes and failures equals total
            $this->assertEquals(
                $feedback['total'],
                $feedback['updated'] + count($feedback['failures']),
                'Sum of updated and failures must equal total'
            );
        }, 100);
    }
}
