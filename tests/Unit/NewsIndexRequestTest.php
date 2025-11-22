<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\NewsIndexRequest;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for NewsIndexRequest validation rules
 * 
 * Tests all validation rules, custom messages, and edge cases
 * for the news index request parameters.
 */
final class NewsIndexRequestTest extends TestCase
{
    use RefreshDatabase;

    private NewsIndexRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new NewsIndexRequest();
    }

    // ========================================
    // AUTHORIZATION TESTS
    // ========================================

    public function test_authorize_returns_true(): void
    {
        // Assert
        $this->assertTrue($this->request->authorize());
    }

    // ========================================
    // CATEGORIES VALIDATION TESTS
    // ========================================

    public function test_categories_can_be_null(): void
    {
        // Arrange
        $data = [];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_categories_must_be_array(): void
    {
        // Arrange
        $data = ['categories' => 'not-an-array'];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'Categories must be an array.',
            $validator->errors()->first('categories')
        );
    }

    public function test_categories_items_must_be_integers(): void
    {
        // Arrange
        $data = ['categories' => ['string', 'another']];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'Each category must be a valid ID.',
            $validator->errors()->first('categories.0')
        );
    }

    public function test_categories_items_must_exist_in_database(): void
    {
        // Arrange
        $data = ['categories' => [99999]];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'One or more selected categories do not exist.',
            $validator->errors()->first('categories.0')
        );
    }

    public function test_categories_accepts_valid_category_ids(): void
    {
        // Arrange
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        $data = ['categories' => [$category1->id, $category2->id]];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_categories_accepts_empty_array(): void
    {
        // Arrange
        $data = ['categories' => []];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    // ========================================
    // AUTHORS VALIDATION TESTS
    // ========================================

    public function test_authors_can_be_null(): void
    {
        // Arrange
        $data = [];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_authors_must_be_array(): void
    {
        // Arrange
        $data = ['authors' => 'not-an-array'];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'Authors must be an array.',
            $validator->errors()->first('authors')
        );
    }

    public function test_authors_items_must_be_integers(): void
    {
        // Arrange
        $data = ['authors' => ['string', 'another']];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'Each author must be a valid ID.',
            $validator->errors()->first('authors.0')
        );
    }

    public function test_authors_items_must_exist_in_database(): void
    {
        // Arrange
        $data = ['authors' => [99999]];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'One or more selected authors do not exist.',
            $validator->errors()->first('authors.0')
        );
    }

    public function test_authors_accepts_valid_user_ids(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $data = ['authors' => [$user1->id, $user2->id]];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_authors_accepts_empty_array(): void
    {
        // Arrange
        $data = ['authors' => []];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    // ========================================
    // DATE VALIDATION TESTS
    // ========================================

    public function test_from_date_can_be_null(): void
    {
        // Arrange
        $data = [];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_from_date_must_be_valid_date(): void
    {
        // Arrange
        $data = ['from_date' => 'invalid-date'];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The from date must be a valid date.',
            $validator->errors()->first('from_date')
        );
    }

    public function test_from_date_must_be_before_or_equal_to_to_date(): void
    {
        // Arrange
        $data = [
            'from_date' => '2025-12-31',
            'to_date' => '2025-01-01',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The from date must be before or equal to the to date.',
            $validator->errors()->first('from_date')
        );
    }

    public function test_from_date_accepts_valid_date(): void
    {
        // Arrange
        $data = ['from_date' => '2025-01-01'];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_to_date_can_be_null(): void
    {
        // Arrange
        $data = [];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_to_date_must_be_valid_date(): void
    {
        // Arrange
        $data = ['to_date' => 'invalid-date'];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The to date must be a valid date.',
            $validator->errors()->first('to_date')
        );
    }

    public function test_to_date_must_be_after_or_equal_to_from_date(): void
    {
        // Arrange
        $data = [
            'from_date' => '2025-12-31',
            'to_date' => '2025-01-01',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The to date must be after or equal to the from date.',
            $validator->errors()->first('to_date')
        );
    }

    public function test_to_date_accepts_valid_date(): void
    {
        // Arrange
        $data = ['to_date' => '2025-12-31'];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_from_date_and_to_date_can_be_equal(): void
    {
        // Arrange
        $data = [
            'from_date' => '2025-06-15',
            'to_date' => '2025-06-15',
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    // ========================================
    // SORT VALIDATION TESTS
    // ========================================

    public function test_sort_can_be_null(): void
    {
        // Arrange
        $data = [];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_sort_must_be_newest_or_oldest(): void
    {
        // Arrange
        $data = ['sort' => 'invalid'];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'Sort must be either "newest" or "oldest".',
            $validator->errors()->first('sort')
        );
    }

    public function test_sort_accepts_newest(): void
    {
        // Arrange
        $data = ['sort' => 'newest'];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_sort_accepts_oldest(): void
    {
        // Arrange
        $data = ['sort' => 'oldest'];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    // ========================================
    // PAGE VALIDATION TESTS
    // ========================================

    public function test_page_can_be_null(): void
    {
        // Arrange
        $data = [];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_page_must_be_integer(): void
    {
        // Arrange
        $data = ['page' => 'not-a-number'];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'Page must be a valid number.',
            $validator->errors()->first('page')
        );
    }

    public function test_page_must_be_at_least_one(): void
    {
        // Arrange
        $data = ['page' => 0];

        // Act
        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'Page must be at least 1.',
            $validator->errors()->first('page')
        );
    }

    public function test_page_accepts_valid_integer(): void
    {
        // Arrange
        $data = ['page' => 5];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    // ========================================
    // COMBINED VALIDATION TESTS
    // ========================================

    public function test_all_valid_parameters_pass_validation(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $data = [
            'categories' => [$category->id],
            'authors' => [$user->id],
            'from_date' => '2025-01-01',
            'to_date' => '2025-12-31',
            'sort' => 'newest',
            'page' => 2,
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_multiple_validation_errors_are_returned(): void
    {
        // Arrange
        $data = [
            'categories' => 'invalid',
            'authors' => 'invalid',
            'from_date' => 'invalid',
            'to_date' => 'invalid',
            'sort' => 'invalid',
            'page' => -1,
        ];

        // Act
        $validator = Validator::make($data, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertCount(6, $validator->errors());
    }
}
