# Design Document

## Overview

The News Page feature provides a dedicated, filterable view of published blog posts optimized for news consumption. This feature will be implemented as a new route (`/news`) with its own controller, request validation, and Blade views. The implementation will leverage Laravel's existing Post, Category, and User models while introducing new filtering and sorting capabilities through query scopes and request objects.

The design emphasizes performance through eager loading, query optimization, and proper indexing. The user interface will be responsive and accessible, with filter state preserved in URL query parameters to enable bookmarking and sharing of filtered views.

### Design Intent & Success Criteria

- Filters (categories/authors/date) persist to query string; clear/reset restores defaults and clears URL.
- Median page load < 350ms server-side with eager loading; indexes on `published_at`, `slug`, foreign keys.
- Accessibility: filter controls reachable via keyboard, proper labels, and status updates for results count.
- Localization: use translated labels and date formats; no hardcoded English copy.
- SEO: semantic headings, metadata, and canonical links; avoid duplicate content for filtered pages.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Browser                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  News Page View (Blade Template)                     │  │
│  │  - Filter Panel (Categories, Authors, Date Range)    │  │
│  │  - Sort Controls                                      │  │
│  │  - News Item Cards                                    │  │
│  │  - Pagination Controls                                │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ HTTP Request (GET /news)
                            │ Query Params: categories[], authors[], 
                            │               from_date, to_date, sort
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Application                       │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Route: web.php                                       │  │
│  │  GET /news → NewsController@index                    │  │
│  └──────────────────────────────────────────────────────┘  │
│                            │                                 │
│                            ▼                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  NewsIndexRequest (Form Request)                     │  │
│  │  - Validates query parameters                         │  │
│  │  - Sanitizes input                                    │  │
│  └──────────────────────────────────────────────────────┘  │
│                            │                                 │
│                            ▼                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  NewsController                                       │  │
│  │  - Builds query with filters                         │  │
│  │  - Applies sorting                                    │  │
│  │  - Paginates results                                  │  │
│  │  - Loads filter options                               │  │
│  └──────────────────────────────────────────────────────┘  │
│                            │                                 │
│                            ▼                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Post Model + Query Scopes                           │  │
│  │  - filterByCategories()                               │  │
│  │  - filterByAuthors()                                  │  │
│  │  - filterByDateRange()                                │  │
│  │  - sortByPublishedDate()                              │  │
│  └──────────────────────────────────────────────────────┘  │
│                            │                                 │
│                            ▼                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Database (MySQL/PostgreSQL)                         │  │
│  │  - posts table                                        │  │
│  │  - categories table                                   │  │
│  │  - category_post pivot table                         │  │
│  │  - users table                                        │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Component Interaction Flow

1. **User Request**: User navigates to `/news` with optional query parameters
2. **Route Handling**: Laravel routes the request to `NewsController@index`
3. **Request Validation**: `NewsIndexRequest` validates and sanitizes query parameters
4. **Query Building**: Controller builds Eloquent query with filters and sorting
5. **Data Retrieval**: Query executes with eager loading for relationships
6. **View Rendering**: Blade template renders with paginated results and filter state
7. **Response**: HTML response sent to browser with preserved filter state in URLs

## Components and Interfaces

### 1. NewsController

**Responsibility**: Handle HTTP requests for the news page, coordinate filtering, sorting, and pagination.

**Methods**:
- `index(NewsIndexRequest $request): View`
  - Retrieves validated filter parameters from request
  - Builds query with applied filters
  - Loads filter options (categories, authors)
  - Paginates results
  - Returns view with data

**Dependencies**:
- `Post` model
- `Category` model
- `User` model
- `NewsIndexRequest`

### 2. NewsIndexRequest (Form Request)

**Responsibility**: Validate and sanitize query parameters for news filtering.

**Validation Rules**:
```php
[
    'categories' => 'nullable|array',
    'categories.*' => 'exists:categories,id',
    'authors' => 'nullable|array',
    'authors.*' => 'exists:users,id',
    'from_date' => 'nullable|date|before_or_equal:to_date',
    'to_date' => 'nullable|date|after_or_equal:from_date',
    'sort' => 'nullable|in:newest,oldest',
    'page' => 'nullable|integer|min:1'
]
```

**Methods**:
- `rules(): array` - Returns validation rules
- `authorize(): bool` - Always returns true (public access)

### 3. Post Model Extensions

**New Query Scopes**:

```php
// Scope: Filter by categories (OR logic)
public function scopeFilterByCategories(Builder $query, array $categoryIds): Builder
{
    return $query->whereHas('categories', function ($q) use ($categoryIds) {
        $q->whereIn('categories.id', $categoryIds);
    });
}

// Scope: Filter by authors (OR logic)
public function scopeFilterByAuthors(Builder $query, array $authorIds): Builder
{
    return $query->whereIn('user_id', $authorIds);
}

// Scope: Filter by date range
public function scopeFilterByDateRange(Builder $query, ?string $fromDate, ?string $toDate): Builder
{
    if ($fromDate) {
        $query->whereDate('published_at', '>=', $fromDate);
    }
    if ($toDate) {
        $query->whereDate('published_at', '<=', $toDate);
    }
    return $query;
}

// Scope: Sort by publication date
public function scopeSortByPublishedDate(Builder $query, string $direction = 'desc'): Builder
{
    return $query->orderBy('published_at', $direction);
}
```

### 4. View Components

**Main View**: `resources/views/news/index.blade.php`
- Page header with title
- Filter panel component
- Results count display
- News items grid
- Pagination controls

**Filter Panel Component**: `resources/views/components/news/filter-panel.blade.php`
- Category checkboxes
- Author checkboxes
- Date range inputs
- Sort dropdown
- Clear filters button

**News Item Card Component**: `resources/views/components/news/news-card.blade.php`
- Post title (linked)
- Excerpt
- Publication date
- Author name (linked)
- Category badges (linked)
- Featured image (lazy loaded)

## Data Models

### Existing Models (No Changes Required)

**Post Model**:
- `id`: Primary key
- `user_id`: Foreign key to users
- `title`: String
- `slug`: String (unique, used for routing)
- `body`: Text
- `description`: Text (excerpt)
- `featured_image`: String (URL)
- `tags`: JSON array
- `published_at`: Timestamp (nullable)
- `created_at`: Timestamp
- `updated_at`: Timestamp

**Relationships**:
- `belongsTo(User::class, 'user_id')` - author
- `belongsToMany(Category::class)` - categories
- `hasMany(Comment::class)` - comments

**Category Model**:
- `id`: Primary key
- `name`: String
- `slug`: String (unique)
- `description`: Text (nullable)
- `created_at`: Timestamp
- `updated_at`: Timestamp

**Relationships**:
- `belongsToMany(Post::class)` - posts

**User Model**:
- `id`: Primary key
- `name`: String
- `email`: String (unique)
- `password`: String (hashed)
- `is_admin`: Boolean
- `is_author`: Boolean
- `is_banned`: Boolean
- `created_at`: Timestamp
- `updated_at`: Timestamp

**Relationships**:
- `hasMany(Post::class)` - posts

### Database Indexes

**Required Indexes** (for performance):
```sql
-- Posts table
CREATE INDEX idx_posts_published_at ON posts(published_at);
CREATE INDEX idx_posts_user_id ON posts(user_id);

-- Category_post pivot table
CREATE INDEX idx_category_post_category_id ON category_post(category_id);
CREATE INDEX idx_category_post_post_id ON category_post(post_id);
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

Based on the prework analysis, I've identified properties that can be verified through property-based testing and examples that test specific scenarios. Some acceptance criteria (particularly those related to database optimization) are implementation details rather than testable functional properties.

### Property Reflection

After reviewing all identified properties, I've consolidated redundant properties:
- Properties 2.5, 3.5, 4.5, 5.5, and 6.5 all test URL parameter persistence and can be combined into a single comprehensive property
- Property 7.3 is redundant with 7.1 (count always matches filtered results)
- Properties 8.1-8.5 all test responsive behavior and can be combined into fewer comprehensive properties

### Core Properties

**Property 1: Default chronological ordering**
*For any* set of published posts, when displayed on the news page without sort parameters, the posts should appear in descending order by publication date (newest first).
**Validates: Requirements 1.2**

**Property 2: Required fields display**
*For any* published post displayed as a news item, the rendered HTML should contain the post title, excerpt, publication date, author name, and all associated category names.
**Validates: Requirements 1.3**

**Property 3: Post detail links**
*For any* published post displayed as a news item, the rendered HTML should contain a clickable link that navigates to the post's detail page using the correct route.
**Validates: Requirements 1.4**

**Property 4: Category filter completeness**
*For any* set of categories in the database, when the news page loads, the filter panel should display all categories that have at least one published post associated with them.
**Validates: Requirements 2.1**

**Property 5: Category filtering (OR logic)**
*For any* set of selected category IDs, the news page should display only posts that are associated with at least one of the selected categories.
**Validates: Requirements 2.2, 2.3**

**Property 6: Author filter completeness**
*For any* set of users in the database, when the news page loads, the filter panel should display all users who have authored at least one published post.
**Validates: Requirements 4.1**

**Property 7: Author filtering (OR logic)**
*For any* set of selected author IDs, the news page should display only posts that were written by at least one of the selected authors.
**Validates: Requirements 4.2, 4.3**

**Property 8: Date range filtering (from date)**
*For any* from date value, the news page should display only posts with a publication date greater than or equal to the from date.
**Validates: Requirements 3.2**

**Property 9: Date range filtering (to date)**
*For any* to date value, the news page should display only posts with a publication date less than or equal to the to date.
**Validates: Requirements 3.3**

**Property 10: Date range filtering (combined)**
*For any* valid from date and to date pair (where from <= to), the news page should display only posts with publication dates within the inclusive range.
**Validates: Requirements 3.4**

**Property 11: Sort order (newest first)**
*For any* set of published posts, when "Newest First" sort is selected, the posts should appear in descending order by publication date.
**Validates: Requirements 5.2**

**Property 12: Sort order (oldest first)**
*For any* set of published posts, when "Oldest First" sort is selected, the posts should appear in ascending order by publication date.
**Validates: Requirements 5.3**

**Property 13: Filter persistence in URL**
*For any* combination of applied filters (categories, authors, date range, sort), the URL query parameters should contain all filter values, and reloading the page with those parameters should restore the exact same filter state.
**Validates: Requirements 2.5, 3.5, 4.5, 5.5**

**Property 14: Sort preserves filters**
*For any* set of applied filters, when the sort order is changed, all previously applied filters should remain active.
**Validates: Requirements 5.4**

**Property 15: Clear filters button visibility**
*For any* filter state, the "Clear All Filters" button should be visible if and only if at least one filter is applied (categories, authors, or date range).
**Validates: Requirements 6.1**

**Property 16: Clear filters action**
*For any* set of applied filters, when the "Clear All Filters" button is clicked, all category, author, and date range filters should be removed, and the URL should contain no filter query parameters.
**Validates: Requirements 6.3, 6.5**

**Property 17: Results count accuracy**
*For any* combination of applied filters, the displayed results count should exactly match the number of posts that satisfy all filter conditions.
**Validates: Requirements 7.1, 7.3**

**Property 18: Pagination info display**
*For any* paginated result set, the page should display both the total count of matching items and the range of items shown on the current page (e.g., "Showing 1-15 of 47 results").
**Validates: Requirements 7.5**

**Property 19: Responsive filter layout**
*For any* viewport width, the filter panel should display in a collapsible panel on mobile (< 768px), a sidebar on tablet (768px-1023px), and a fixed sidebar on desktop (>= 1024px).
**Validates: Requirements 8.1, 8.2, 8.3**

**Property 20: Responsive news item layout**
*For any* viewport width, news items should display as full-width stacked cards on mobile (< 768px) and in a multi-column grid on larger screens.
**Validates: Requirements 8.4**

**Property 21: Locale-aware navigation**
*For any* supported locale, the "News" navigation link should display the label in the current locale's language.
**Validates: Requirements 9.4**

**Property 22: Lazy loading images**
*For any* news item with a featured image, the image element should have the `loading="lazy"` attribute to enable browser-native lazy loading.
**Validates: Requirements 10.5**

### Edge Cases

**Edge Case 1: Empty results**
When filters are applied that match no posts, the page should display "0 results found" with a message suggesting filter adjustment.
**Validates: Requirements 7.4**

### Example Tests

**Example 1: News page route and title**
Navigating to `/news` should display a page with the title "News" and show all published posts.
**Validates: Requirements 1.1**

**Example 2: Pagination threshold**
When exactly 16 published posts exist, page 1 should display 15 items and page 2 should display 1 item.
**Validates: Requirements 1.5**

**Example 3: Date range filter controls**
The news page should display date range filter controls with "from date" and "to date" input fields.
**Validates: Requirements 3.1**

**Example 4: No filters applied state**
When no category filters are applied, all published posts should be displayed.
**Validates: Requirements 2.4**

**Example 5: No author filters applied state**
When no author filters are applied, all published posts should be displayed.
**Validates: Requirements 4.4**

**Example 6: Sort controls display**
The news page should display sort controls with "Newest First" and "Oldest First" options.
**Validates: Requirements 5.1**

**Example 7: No filters button hidden**
When no filters are applied, the "Clear All Filters" button should not be visible.
**Validates: Requirements 6.2**

**Example 8: Default view after clearing**
After clicking "Clear All Filters", the page should display all published posts.
**Validates: Requirements 6.4**

**Example 9: Total count without filters**
When no filters are applied, the displayed count should equal the total number of published posts.
**Validates: Requirements 7.2**

**Example 10: News link in navigation**
The main navigation menu should contain a "News" link.
**Validates: Requirements 9.1**

**Example 11: Active navigation state**
When on the news page, the "News" navigation link should have active styling.
**Validates: Requirements 9.2**

**Example 12: Mobile navigation**
The mobile navigation menu should include the "News" link.
**Validates: Requirements 9.3**

**Example 13: Clean navigation link**
Clicking the "News" navigation link should navigate to `/news` without any query parameters.
**Validates: Requirements 9.5**

## Error Handling

### Validation Errors

**Invalid Query Parameters**:
- Invalid category IDs: Silently ignore non-existent categories
- Invalid author IDs: Silently ignore non-existent authors
- Invalid date formats: Return 422 validation error with clear message
- Invalid date range (from > to): Return 422 validation error
- Invalid sort value: Default to "newest"
- Invalid page number: Redirect to page 1

**Error Response Format**:
```php
// For AJAX requests
{
    "message": "The given data was invalid.",
    "errors": {
        "from_date": ["The from date must be a valid date."],
        "to_date": ["The to date must be after or equal to from date."]
    }
}

// For regular requests
// Redirect back with errors in session
```

### Edge Cases

**No Results**:
- Display empty state message
- Show "Clear All Filters" button if filters are applied
- Suggest adjusting filters

**Database Errors**:
- Log error details
- Display generic error message to user
- Provide link to return to homepage

**Missing Relationships**:
- Handle posts without authors gracefully (show "Unknown Author")
- Handle posts without categories (don't display category section)

## Testing Strategy

### Unit Testing

**Controller Tests**:
- Test that index method returns correct view
- Test that filters are applied correctly
- Test that pagination works
- Test that filter options are loaded
- Test error handling for invalid parameters

**Request Validation Tests**:
- Test all validation rules
- Test edge cases (boundary values)
- Test error messages

**Model Scope Tests**:
- Test each query scope independently
- Test scope combinations
- Test with empty datasets
- Test with edge cases

### Property-Based Testing

The testing strategy will use property-based testing to verify the correctness properties defined above. We'll use a PHP property-based testing library (such as Eris or php-quickcheck) to generate random test data and verify properties hold across many inputs.

**Property Test Configuration**:
- Minimum 100 iterations per property test
- Generate random posts with varying publication dates
- Generate random categories and author assignments
- Test all filter combinations

**Test Data Generators**:
```php
// Generator for published posts
function publishedPostGenerator(): Generator {
    // Generate posts with random:
    // - titles
    // - publication dates (past dates only)
    // - author assignments
    // - category assignments (0-5 categories)
    // - excerpts
}

// Generator for filter parameters
function filterParamsGenerator(): Generator {
    // Generate random combinations of:
    // - category IDs (0-10 categories)
    // - author IDs (0-5 authors)
    // - date ranges (valid and edge cases)
    // - sort orders
}
```

**Property Test Tags**:
Each property-based test will be tagged with a comment referencing the design document:
```php
/**
 * Feature: news-page, Property 5: Category filtering (OR logic)
 * Validates: Requirements 2.2, 2.3
 */
public function test_category_filtering_or_logic(): void
{
    // Property test implementation
}
```

### Integration Testing

**Browser Tests** (Laravel Dusk):
- Test complete user flows
- Test responsive behavior at different viewport sizes
- Test filter interactions
- Test pagination navigation
- Test URL parameter persistence

**Feature Tests**:
- Test full HTTP request/response cycle
- Test middleware integration
- Test view rendering
- Test database queries

### Performance Testing

**Query Performance**:
- Verify N+1 queries are avoided (use Laravel Debugbar)
- Verify indexes are used (EXPLAIN queries)
- Test with large datasets (1000+ posts)
- Measure response times

**Load Testing**:
- Test concurrent users
- Test with various filter combinations
- Measure database query times
- Identify bottlenecks

## Implementation Notes

### Performance Considerations

1. **Eager Loading**: Always eager load `author` and `categories` relationships to avoid N+1 queries
2. **Query Optimization**: Use `whereHas` for filtering but avoid loading unnecessary data
3. **Caching**: Consider caching filter options (categories, authors) for short periods
4. **Pagination**: Use cursor pagination for better performance with large datasets (future enhancement)
5. **Indexes**: Ensure proper indexes exist on `published_at`, `user_id`, and pivot table columns

### Accessibility

1. **Semantic HTML**: Use proper heading hierarchy, landmarks, and semantic elements
2. **ARIA Labels**: Add appropriate ARIA labels to filter controls
3. **Keyboard Navigation**: Ensure all interactive elements are keyboard accessible
4. **Focus Management**: Maintain logical focus order
5. **Screen Reader Support**: Provide descriptive labels and announcements for dynamic content

### Responsive Design

**Breakpoints**:
- Mobile: < 768px
- Tablet: 768px - 1023px
- Desktop: >= 1024px

**Layout Strategy**:
- Mobile-first approach
- Use CSS Grid for news item layout
- Use Flexbox for filter panel
- Collapsible filter panel on mobile (Alpine.js)

### Internationalization

**Translatable Strings**:
- Page title: "News"
- Filter labels: "Categories", "Authors", "Date Range", "Sort By"
- Sort options: "Newest First", "Oldest First"
- Button labels: "Clear All Filters", "Apply Filters"
- Empty state messages
- Pagination labels

**Translation Files**:
- Add translations to `lang/{locale}/news.php`
- Use Laravel's `__()` helper in views
- Support RTL languages (future enhancement)

### Security Considerations

1. **Input Validation**: Strict validation of all query parameters
2. **SQL Injection**: Use Eloquent query builder (parameterized queries)
3. **XSS Prevention**: Escape all output in Blade templates (automatic)
4. **CSRF Protection**: Not required for GET requests
5. **Rate Limiting**: Apply rate limiting to prevent abuse

### Future Enhancements

1. **Search Functionality**: Add full-text search across post titles and content
2. **Tag Filtering**: Add ability to filter by post tags
3. **Saved Filters**: Allow users to save and name filter combinations
4. **RSS Feed**: Provide RSS feed for filtered results
5. **Export**: Allow exporting filtered results as CSV/PDF
6. **Advanced Sorting**: Add sorting by view count, comment count, etc.
7. **Infinite Scroll**: Alternative to pagination for better UX
8. **Filter Presets**: Quick filter buttons for common combinations (e.g., "This Week", "This Month")
