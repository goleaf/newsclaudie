# News View Rendering Property Testing

**Last Updated**: 2025-11-23  
**Test File**: `tests/Unit/NewsViewRenderingPropertyTest.php`  
**Component Under Test**: `resources/views/components/news/news-card.blade.php`

## Overview

This document describes the property-based testing approach for the News View Rendering feature. These tests verify that the news card component correctly renders all required fields and maintains proper HTML structure regardless of post content.

## What is Property-Based Testing?

Property-based testing verifies universal properties that should hold true across all valid inputs. Instead of testing specific examples, we test general rules by running many iterations with randomized data.

### Example Property

**Property**: "All news cards must display the post title, publication date, and author name"

This property must be true whether we have:
- Short titles or long titles
- Posts from yesterday or 30 days ago
- Posts with 0 categories or 5 categories
- Posts with or without featured images

## Properties Tested

### Property 2: Required Fields Display

**Universal Rule**: Every rendered news card must contain all required fields: title, description (if exists), publication date, author name, and all associated category names.

**Test Coverage**:
- ✅ Post title is present
- ✅ Post description is present (when exists)
- ✅ Publication date is formatted correctly
- ✅ Author name is present
- ✅ All category names are present
- ✅ Graceful handling of missing description
- ✅ Graceful handling of no categories

**Requirements Validated**: 1.3

### Property 3: Post Detail Links

**Universal Rule**: Every news card must contain clickable links to the post detail page using the correct route, with multiple link instances for better UX.

**Test Coverage**:
- ✅ Post detail route is present
- ✅ Route uses correct post slug
- ✅ Multiple link instances exist (title, read more)
- ✅ Links use proper href attribute format

**Requirements Validated**: 1.4

### Property 22: Lazy Loading Images

**Universal Rule**: All featured images must have the loading="lazy" attribute for performance, and default images should not be rendered.

**Test Coverage**:
- ✅ Featured images have loading="lazy"
- ✅ No image tag when no featured image
- ✅ Default images (default.jpg) not rendered
- ✅ Image src matches featured_image field

**Requirements Validated**: 10.5

### Edge Case Properties

**Universal Rules**: The component must handle edge cases gracefully without errors.

**Test Coverage**:
- ✅ Posts without description render correctly
- ✅ Posts without categories render correctly
- ✅ Empty/null/whitespace descriptions handled
- ✅ No broken HTML tags

**Requirements Validated**: 1.3

## Test Strategy

### Iteration Counts

- **Standard tests**: 10 iterations (balance between coverage and performance)
- **Edge case tests**: 10 iterations (ensure consistency)

### Randomization Strategy

Each iteration creates a different scenario:

```php
// Random post content
$post = Post::factory()->create([
    'title' => $faker->sentence(),
    'description' => $faker->paragraph(),
    'published_at' => now()->subDays($faker->numberBetween(1, 30)),
]);

// Random category associations (0-5)
$categoryCount = $faker->numberBetween(0, 5);
if ($categoryCount > 0) {
    $categories = Category::factory()->count($categoryCount)->create();
    $post->categories()->attach($categories->pluck('id'));
}
```

### Data Cleanup

Each iteration cleans up after itself to ensure test isolation:

```php
// Cleanup
$post->categories()->detach();
$post->delete();
$author->delete();
if (isset($categories)) {
    foreach ($categories as $category) {
        $category->delete();
    }
}
```

## Running the Tests

### Run all view rendering tests
```bash
php artisan test tests/Unit/NewsViewRenderingPropertyTest.php
```

### Run specific test
```bash
php artisan test --filter=test_required_fields_display
```

### Run with verbose output
```bash
php artisan test tests/Unit/NewsViewRenderingPropertyTest.php --verbose
```

### Run in parallel
```bash
php artisan test tests/Unit/NewsViewRenderingPropertyTest.php --parallel
```

### Run by group
```bash
# All view rendering tests
php artisan test --group=news-view

# Edge case tests
php artisan test --group=edge-cases

# Performance tests
php artisan test --group=performance
```

## Test Results

### Expected Output

```
PASS  Tests\Unit\NewsViewRenderingPropertyTest
✓ required fields display                         0.67s
✓ post detail links                               0.12s
✓ lazy loading images                             0.19s
✓ required fields display without description     0.09s
✓ required fields display without categories      0.10s

Tests:    5 passed (226 assertions)
Duration: 1.35s
```

### Assertion Breakdown

Each test makes multiple assertions per iteration:

- **Required fields**: ~5 assertions per iteration × 10 iterations = 50 assertions
- **Post detail links**: ~3 assertions per iteration × 10 iterations = 30 assertions
- **Lazy loading images**: ~9 assertions per iteration × 10 iterations = 90 assertions
- **Without description**: ~4 assertions per iteration × 10 iterations = 40 assertions
- **Without categories**: ~4 assertions per iteration × 10 iterations = 40 assertions

**Total**: ~226 assertions across all tests (varies slightly due to randomization)

## Understanding Test Failures

### Failure: Required field missing from HTML

```
Failed asserting that 'rendered HTML...' contains "Post Title Here".

Post title 'Post Title Here' should be present in rendered HTML
```

**Diagnosis**: The component is not rendering a required field.

**Check**:
1. `resources/views/components/news/news-card.blade.php` template
2. Verify the field is being passed to the component
3. Check for conditional rendering that might hide the field
4. Verify the post model has the field loaded

### Failure: Incorrect post detail route

```
Failed asserting that 'rendered HTML...' contains "http://localhost/posts/example-slug".

Post detail route 'http://localhost/posts/example-slug' should be present in rendered HTML
```

**Diagnosis**: The component is using the wrong route or route parameters.

**Check**:
1. `route('posts.show', $post)` usage in component
2. Post model's `getRouteKeyName()` method
3. Route definition in `routes/web.php`
4. Post slug generation

### Failure: Missing lazy loading attribute

```
Failed asserting that 'rendered HTML...' contains "loading=\"lazy\"".

Image element should have loading='lazy' attribute for performance
```

**Diagnosis**: Images are not using lazy loading.

**Check**:
1. `<img>` tag in news-card component
2. Verify `loading="lazy"` attribute is present
3. Check for conditional image rendering
4. Verify featured_image field is not null

### Failure: Empty description paragraph rendered

```
Failed asserting that 'rendered HTML...' does not contain "<p class=\"...\"></p>".

Empty description paragraph should not be rendered
```

**Diagnosis**: Component is rendering empty HTML elements.

**Check**:
1. Add `@if($post->description)` conditional
2. Use `trim()` to check for whitespace-only content
3. Verify the paragraph is only rendered when content exists

## Integration with News Page

### How the Component is Used

```php
// In NewsController
public function index(NewsIndexRequest $request)
{
    $posts = Post::query()
        ->with(['author', 'categories'])
        ->published()
        ->latest('published_at')
        ->paginate(15);
    
    return view('news.index', [
        'posts' => $posts,
        // ...
    ]);
}
```

### View Usage

```blade
{{-- resources/views/news/index.blade.php --}}
<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
    @foreach($posts as $post)
        <x-news.news-card :post="$post" />
    @endforeach
</div>
```

### Component Structure

```blade
{{-- resources/views/components/news/news-card.blade.php --}}
@props(['post'])

<article class="card">
    {{-- Featured Image with lazy loading --}}
    @if($post->featured_image && !str_contains($post->featured_image, 'default.jpg'))
        <img src="{{ $post->featured_image }}" 
             alt="{{ $post->title }}" 
             loading="lazy">
    @endif
    
    {{-- Title with link --}}
    <h2>
        <a href="{{ route('posts.show', $post) }}">
            {{ $post->title }}
        </a>
    </h2>
    
    {{-- Description --}}
    @if($post->description)
        <p>{{ $post->description }}</p>
    @endif
    
    {{-- Meta information --}}
    <div class="meta">
        <time>{{ $post->published_at->format('M j, Y') }}</time>
        <span>by {{ $post->author->name }}</span>
    </div>
    
    {{-- Categories --}}
    @if($post->categories->isNotEmpty())
        <div class="categories">
            @foreach($post->categories as $category)
                <a href="{{ route('categories.show', $category) }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    @endif
    
    {{-- Read more link --}}
    <a href="{{ route('posts.show', $post) }}">
        Read more
    </a>
</article>
```

## Related Documentation

- [Quick Reference](NEWS_VIEW_RENDERING_QUICK_REFERENCE.md) - Fast-access commands and tips
- [Property Testing Guide](../PROPERTY_TESTING.md) - General property testing approach
- [News Filter Options Testing](NEWS_FILTER_OPTIONS_TESTING.md) - Related news feature tests
- [News Feature Requirements](../../.kiro/specs/news-page/requirements.md) - Feature requirements
- [Test Coverage](../../docs/TEST_COVERAGE.md) - Overall test coverage

## Maintenance Notes

### When to Update These Tests

1. **When component structure changes**: If news-card layout or fields change
2. **When new fields added**: Add assertions for new required fields
3. **When styling changes**: Verify properties still hold after CSS changes
4. **When Post model changes**: Update if relationships or accessors change

### Performance Considerations

These tests involve view rendering and can be slower than pure unit tests. To optimize:

1. **Use fewer iterations in development**: Reduce from 10 to 3-5 for faster feedback
2. **Run in parallel**: Use `--parallel` flag for faster execution
3. **Use SQLite in memory**: Faster than disk-based databases
4. **Profile slow tests**: Use `--profile` to identify bottlenecks

### Adding New Properties

When adding new fields to the news card, follow this pattern:

```php
/**
 * Test Property X: [Field name] display
 * 
 * **Property**: [Universal rule that must hold]
 * 
 * **Validates**: Requirement X.X
 * 
 * @test
 * @group property-testing
 * @group news-view
 */
public function test_[field_name]_display(): void
{
    for ($i = 0; $i < 10; $i++) {
        // Setup random data
        // Render component
        // Assert property
        // Cleanup
    }
}
```

## Troubleshooting

### Tests are too slow

**Solution**: Reduce iterations or use parallel execution
```bash
# Edit test file temporarily, change loop count
for ($i = 0; $i < 3; $i++) {  // Instead of 10

# Or use parallel execution
php artisan test tests/Unit/NewsViewRenderingPropertyTest.php --parallel
```

### Random failures

**Solution**: Check for non-deterministic behavior
- Ensure relationships are loaded with `$post->load('author', 'categories')`
- Check for time-dependent logic (use `Carbon::setTestNow()`)
- Verify test isolation (proper cleanup)

### Blade rendering errors

**Solution**: Check component syntax and data
```bash
# Clear view cache
php artisan view:clear

# Check for syntax errors
php artisan view:cache
```

### Memory issues

**Solution**: Reduce batch sizes or iterations
```php
// Instead of 0-5 categories, use 0-3
$categoryCount = $faker->numberBetween(0, 3);
```

## Contributing

When contributing to these tests:

1. ✅ Follow the existing documentation pattern
2. ✅ Add clear property descriptions
3. ✅ Include requirement references
4. ✅ Use appropriate test groups
5. ✅ Clean up test data
6. ✅ Update this documentation
7. ✅ Add examples to quick reference

## Questions?

For questions about these tests, see:
- [Quick Reference](NEWS_VIEW_RENDERING_QUICK_REFERENCE.md)
- [Property Testing Guide](../PROPERTY_TESTING.md)
- [Test Coverage Documentation](../../docs/TEST_COVERAGE.md)
- Project maintainers
