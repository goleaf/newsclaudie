# News View Rendering Testing - Quick Reference

**Test File**: `tests/Unit/NewsViewRenderingPropertyTest.php`  
**Component**: `resources/views/components/news/news-card.blade.php`  
**Full Documentation**: [Property Testing Guide](../PROPERTY_TESTING.md)

## Quick Commands

```bash
# Run all view rendering tests
php artisan test tests/Unit/NewsViewRenderingPropertyTest.php

# Run specific test
php artisan test --filter=test_required_fields_display

# Run with specific group
php artisan test --group=news-view

# Run in parallel
php artisan test tests/Unit/NewsViewRenderingPropertyTest.php --parallel
```

## Test Summary

| Test | Property | Iterations | Validates |
|------|----------|------------|-----------|
| `test_required_fields_display` | All required fields present | 10 | Req 1.3 |
| `test_post_detail_links` | Correct post detail links | 10 | Req 1.4 |
| `test_lazy_loading_images` | Images have loading="lazy" | 10 | Req 10.5 |
| `test_required_fields_display_without_description` | Graceful handling of missing description | 10 | Req 1.3 |
| `test_required_fields_display_without_categories` | Graceful handling of no categories | 10 | Req 1.3 |

**Total**: 5 tests, ~226 assertions, 50 iterations

## Properties Tested

### Property 2: Required Fields Display
**Rule**: All required fields must be present in rendered HTML

**Verified**:
- ✅ Post title displayed
- ✅ Post description displayed (if exists)
- ✅ Publication date formatted correctly
- ✅ Author name displayed
- ✅ All category names displayed

### Property 3: Post Detail Links
**Rule**: Correct links to post detail pages

**Verified**:
- ✅ Post detail route present
- ✅ Route uses correct post slug
- ✅ Multiple link instances (title, read more)
- ✅ Proper href attribute format

### Property 22: Lazy Loading Images
**Rule**: Images have loading="lazy" attribute

**Verified**:
- ✅ Featured images have loading="lazy"
- ✅ No image tag when no featured image
- ✅ Default images not rendered
- ✅ Image src matches featured_image

## Common Failures

### Missing Required Field
```
Post title 'Example Title' should be present in rendered HTML
```
**Fix**: Check news-card component template, ensure field is rendered

### Incorrect Link Route
```
Post detail route 'http://localhost/posts/example-slug' should be present
```
**Fix**: Verify route() helper usage in component, check post slug

### Missing Lazy Loading
```
Image element should have loading='lazy' attribute for performance
```
**Fix**: Add loading="lazy" to <img> tag in news-card component

## Test Groups

```bash
# All property tests
php artisan test --group=property-testing

# News feature tests
php artisan test --group=news-page

# View rendering tests
php artisan test --group=news-view

# Edge case tests
php artisan test --group=edge-cases

# Performance tests
php artisan test --group=performance
```

## Key Concepts

### Required Fields
- Title (always required)
- Description (optional, gracefully handled)
- Publication date (formatted as "M j, Y")
- Author name
- Categories (optional, gracefully handled)

### Edge Cases Tested
- Posts without description (null, empty, whitespace)
- Posts without categories (empty collection)
- Posts without featured image
- Posts with default.jpg (should not render)

## Performance Tips

1. **Reduce iterations for development**:
   ```bash
   # Edit test file temporarily, change loop count from 10 to 3
   for ($i = 0; $i < 3; $i++) {
   ```

2. **Use parallel execution**:
   ```bash
   php artisan test tests/Unit/NewsViewRenderingPropertyTest.php --parallel
   ```

3. **Profile slow tests**:
   ```bash
   php artisan test tests/Unit/NewsViewRenderingPropertyTest.php --profile
   ```

## Related Files

- **Property Testing Guide**: [../PROPERTY_TESTING.md](../PROPERTY_TESTING.md)
- **Component Template**: `resources/views/components/news/news-card.blade.php`
- **Requirements**: `.kiro/specs/news-page/requirements.md`
- **Test Coverage**: `docs/testing/TEST_COVERAGE.md`

## Expected Output

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

## Maintenance Checklist

- [ ] Update tests when news-card component changes
- [ ] Add new property tests for new fields
- [ ] Verify properties hold after styling changes
- [ ] Update when Post model relationships change
- [ ] Keep documentation in sync with code

## Questions?

See [Property Testing Guide](../PROPERTY_TESTING.md) for general concepts and approach.
