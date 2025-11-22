# Implementation Plan

- [x] 1. Set up core infrastructure and routing
  - Create NewsController with index method
  - Add `/news` route to web.php
  - Create NewsIndexRequest for query parameter validation
  - _Requirements: 1.1, 9.5_

- [x] 2. Extend Post model with query scopes
  - [x] 2.1 Implement filterByCategories scope for OR logic filtering
    - Add scope to filter posts by array of category IDs
    - Use whereHas with whereIn for efficient querying
    - _Requirements: 2.2, 2.3_
  
  - [x] 2.2 Write property test for category filtering
    - **Property 5: Category filtering (OR logic)**
    - **Validates: Requirements 2.2, 2.3**
  
  - [x] 2.3 Implement filterByAuthors scope for OR logic filtering
    - Add scope to filter posts by array of author IDs
    - Use whereIn on user_id column
    - _Requirements: 4.2, 4.3_
  
  - [x] 2.4 Write property test for author filtering
    - **Property 7: Author filtering (OR logic)**
    - **Validates: Requirements 4.2, 4.3**
  
  - [x] 2.5 Implement filterByDateRange scope
    - Add scope accepting optional from_date and to_date parameters
    - Use whereDate for date comparisons
    - _Requirements: 3.2, 3.3, 3.4_
  
  - [x] 2.6 Write property tests for date range filtering
    - **Property 8: Date range filtering (from date)**
    - **Property 9: Date range filtering (to date)**
    - **Property 10: Date range filtering (combined)**
    - **Validates: Requirements 3.2, 3.3, 3.4**
  
  - [x] 2.7 Implement sortByPublishedDate scope
    - Add scope accepting direction parameter (asc/desc)
    - Order by published_at column
    - _Requirements: 5.2, 5.3_
  
  - [x] 2.8 Write property tests for sort ordering
    - **Property 11: Sort order (newest first)**
    - **Property 12: Sort order (oldest first)**
    - **Validates: Requirements 5.2, 5.3**

- [-] 3. Implement NewsController logic
  - [x] 3.1 Build query with filters and eager loading
    - Apply category, author, and date range filters from request
    - Eager load author and categories relationships
    - Apply sort order based on request parameter
    - _Requirements: 1.2, 2.2, 3.2, 4.2, 5.2, 10.4_
  
  - [x] 3.2 Load filter options for UI
    - Query categories that have published posts
    - Query users who have authored published posts
    - _Requirements: 2.1, 4.1_
  
  - [ ]* 3.3 Write property tests for filter options
    - **Property 4: Category filter completeness**
    - **Property 6: Author filter completeness**
    - **Validates: Requirements 2.1, 4.1**
  
  - [x] 3.3 Implement pagination
    - Paginate results with 15 items per page
    - Preserve query parameters in pagination links
    - _Requirements: 1.5_
  
  - [x] 3.4 Calculate and pass results count
    - Get total count of filtered results
    - Pass count to view for display
    - _Requirements: 7.1, 7.2, 7.3_
  
  - [ ]* 3.5 Write property test for results count
    - **Property 17: Results count accuracy**
    - **Validates: Requirements 7.1, 7.3**

- [x] 4. Create view components and templates
  - [x] 4.1 Create main news index view
    - Create resources/views/news/index.blade.php
    - Add page header with "News" title
    - Include filter panel component
    - Display results count
    - Include news items grid
    - Add pagination controls
    - _Requirements: 1.1, 1.3, 7.1_
  
  - [x] 4.2 Create filter panel component
    - Create resources/views/components/news/filter-panel.blade.php
    - Add category checkboxes with labels
    - Add author checkboxes with labels
    - Add date range inputs (from_date, to_date)
    - Add sort dropdown (newest/oldest)
    - Add "Clear All Filters" button with conditional visibility
    - _Requirements: 2.1, 3.1, 4.1, 5.1, 6.1_
  
  - [x] 4.3 Create news card component
    - Create resources/views/components/news/news-card.blade.php
    - Display post title as link to detail page
    - Display excerpt/description
    - Display publication date
    - Display author name as link
    - Display category badges as links
    - Add lazy loading for featured images
    - _Requirements: 1.3, 1.4, 10.5_
  
  - [ ]* 4.4 Write property tests for view rendering
    - **Property 2: Required fields display**
    - **Property 3: Post detail links**
    - **Property 22: Lazy loading images**
    - **Validates: Requirements 1.3, 1.4, 10.5**
  
  - [x] 4.5 Create empty state view
    - Display "0 results found" message when no results
    - Show suggestion to adjust filters
    - _Requirements: 7.4_

- [x] 5. Implement filter state management
  - [x] 5.1 Add URL parameter preservation
    - Ensure all filter parameters persist in pagination links
    - Ensure sort order persists when filters change
    - Ensure filters persist when sort changes
    - _Requirements: 2.5, 3.5, 4.5, 5.4, 5.5_
  
  - [ ]* 5.2 Write property tests for filter persistence
    - **Property 13: Filter persistence in URL**
    - **Property 14: Sort preserves filters**
    - **Validates: Requirements 2.5, 3.5, 4.5, 5.4, 5.5**
  
  - [x] 5.3 Implement clear filters functionality
    - Add button visibility logic (show only when filters applied)
    - Implement clear action to remove all filters
    - Redirect to clean /news URL
    - _Requirements: 6.1, 6.2, 6.3, 6.5_
  
  - [ ]* 5.4 Write property tests for clear filters
    - **Property 15: Clear filters button visibility**
    - **Property 16: Clear filters action**
    - **Validates: Requirements 6.1, 6.3, 6.5**

- [ ] 6. Add responsive design and styling
  - [ ] 6.1 Implement mobile layout (< 768px)
    - Make filter panel collapsible with Alpine.js
    - Stack news items vertically with full-width cards
    - _Requirements: 8.1, 8.4_
  
  - [ ] 6.2 Implement tablet layout (768px - 1023px)
    - Display filters in sidebar layout
    - Adjust news item grid for tablet
    - _Requirements: 8.2_
  
  - [ ] 6.3 Implement desktop layout (>= 1024px)
    - Display filters in fixed sidebar
    - Show news items in multi-column grid
    - _Requirements: 8.3_
  
  - [ ]* 6.4 Write property tests for responsive layouts
    - **Property 19: Responsive filter layout**
    - **Property 20: Responsive news item layout**
    - **Validates: Requirements 8.1, 8.2, 8.3, 8.4**

- [ ] 7. Add navigation integration
  - [ ] 7.1 Add "News" link to main navigation
    - Update resources/views/components/navigation/main.blade.php
    - Add news route to primaryLinks array
    - Ensure link appears in both desktop and mobile navigation
    - _Requirements: 9.1, 9.3_
  
  - [ ] 7.2 Implement active state highlighting
    - Add route matching for news.* pattern
    - Apply active styling when on news page
    - _Requirements: 9.2_
  
  - [ ] 7.3 Add translation support
    - Create lang/en/news.php with all translatable strings
    - Add "nav.news" key to lang/en.json
    - Use __() helper in all views
    - _Requirements: 9.4_
  
  - [ ]* 7.4 Write property test for locale-aware navigation
    - **Property 21: Locale-aware navigation**
    - **Validates: Requirements 9.4**

- [ ] 8. Add database indexes for performance
  - [ ] 8.1 Create migration for indexes
    - Add index on posts.published_at
    - Add index on posts.user_id (if not exists)
    - Add indexes on category_post pivot table
    - _Requirements: 10.1_
  
  - [ ] 8.2 Run migration and verify indexes
    - Execute migration
    - Verify indexes with EXPLAIN queries
    - _Requirements: 10.1_

- [ ] 9. Write example tests for specific scenarios
  - [ ]* 9.1 Test news page route and title
    - Verify /news displays page with "News" title
    - **Validates: Requirements 1.1**
  
  - [ ]* 9.2 Test pagination threshold
    - Verify 16 posts results in 15 on page 1, 1 on page 2
    - **Validates: Requirements 1.5**
  
  - [ ]* 9.3 Test date range filter controls display
    - Verify from_date and to_date inputs are present
    - **Validates: Requirements 3.1**
  
  - [ ]* 9.4 Test default states
    - Verify all posts shown when no category filters applied
    - Verify all posts shown when no author filters applied
    - **Validates: Requirements 2.4, 4.4**
  
  - [ ]* 9.5 Test sort controls display
    - Verify "Newest First" and "Oldest First" options present
    - **Validates: Requirements 5.1**
  
  - [ ]* 9.6 Test clear filters button states
    - Verify button hidden when no filters applied
    - Verify default view after clearing filters
    - **Validates: Requirements 6.2, 6.4**
  
  - [ ]* 9.7 Test results count display
    - Verify count equals total published posts when no filters
    - **Validates: Requirements 7.2**
  
  - [ ]* 9.8 Test navigation integration
    - Verify "News" link present in navigation
    - Verify active state on news page
    - Verify link in mobile navigation
    - Verify clean URL without query parameters
    - **Validates: Requirements 9.1, 9.2, 9.3, 9.5**
  
  - [ ]* 9.9 Test pagination info display
    - **Property 18: Pagination info display**
    - **Validates: Requirements 7.5**
  
  - [ ]* 9.10 Test empty results edge case
    - Verify empty state message when no results match filters
    - **Validates: Requirements 7.4**

- [ ] 10. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 11. Write integration tests
  - [ ]* 11.1 Test complete filter flow
    - Test applying multiple filters together
    - Test filter combinations with pagination
    - Test filter state preservation across navigation
  
  - [ ]* 11.2 Test error handling
    - Test invalid date formats
    - Test invalid date ranges (from > to)
    - Test non-existent category/author IDs
    - Test invalid sort values

- [ ] 12. Final checkpoint - Verify all requirements
  - Ensure all tests pass, ask the user if questions arise.
