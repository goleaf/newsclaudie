# Implementation Plan

- [x] 1. Set up shared Livewire traits and utilities
  - Create reusable traits for common admin functionality
  - Establish patterns for pagination, search, sorting, and bulk actions
  - _Requirements: 7.1, 7.2, 8.1, 9.1_

- [x] 1.1 Create ManagesBulkActions trait
  - Implement selection tracking, select all, and bulk operation methods
  - Add query string support for maintaining selection state
  - _Requirements: 8.1, 8.2, 8.3_

- [x] 1.2 Create ManagesSearch trait
  - Implement search with debouncing and query string persistence
  - Add methods for applying search to query builders
  - _Requirements: 7.1, 7.4_

- [x] 1.3 Create ManagesSorting trait
  - Implement sortable column logic with direction toggle
  - Add query string persistence for sort state
  - _Requirements: 9.1, 9.2, 9.4_

- [x] 1.4 Install and configure property-based testing library
  - Install Pest Property Testing plugin via composer
  - Configure minimum iterations (100) in phpunit.xml or pest config
  - _Requirements: All testing requirements_

- [x] 1.5 Write property tests for shared traits
  - **Property 10: Search filtering accuracy**
  - **Validates: Requirements 7.1**
  - **Property 15: Column sort ordering**
  - **Validates: Requirements 9.1, 9.2**
  - **Property 18: Select all page scope**
  - **Validates: Requirements 8.2**

- [x] 2. Implement Categories CRUD with Livewire
  - Build complete categories management as the foundation pattern
  - Includes index, form modal, inline editing, and validation
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_

- [x] 2.1 Create CategoriesIndex Volt component
  - Implement index view with search, sorting, and pagination
  - Add delete functionality with confirmation
  - Integrate ManagesPerPage, ManagesSearch, and ManagesSorting traits
  - _Requirements: 2.1, 2.6, 2.7_

- [x] 2.2 Create CategoryForm Volt component for modal
  - Implement create/edit form with real-time validation
  - Add slug auto-generation from name
  - Handle manual slug editing to stop auto-generation
  - _Requirements: 2.2, 2.3, 2.4, 2.5_

- [x] 2.3 Write property test for slug auto-generation
  - **Property 25: Slug auto-generation from name**
  - **Validates: Requirements 2.3**

- [x] 2.4 Write property test for manual slug editing
  - **Property 26: Manual slug edit stops auto-generation**
  - **Validates: Requirements 2.4**

- [x] 2.5 Write property test for slug validation
  - **Property 7: Slug format validation**
  - **Validates: Requirements 2.4**

- [x] 2.6 Write property test for slug uniqueness
  - **Property 8: Uniqueness validation**
  - **Validates: Requirements 2.5**

- [x] 2.7 Write property test for category persistence
  - **Property 1: Data persistence round-trip**
  - **Validates: Requirements 2.5**

- [x] 2.8 Write property test for category deletion
  - **Property 2: Deletion removes resource**
  - **Validates: Requirements 2.6**

- [x] 2.9 Write property test for category post count
  - **Property 31: Category post count accuracy**
  - **Validates: Requirements 2.1, 2.7**

- [x] 3. Implement Posts CRUD with Livewire
  - Build posts management with category relationships
  - Includes publication status toggle and category assignment
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7_

- [x] 3.1 Create PostsIndex Volt component
  - Implement index with search, filters (status, category), sorting, and pagination
  - Add delete functionality and publication status toggle
  - Integrate all management traits
  - _Requirements: 1.1, 1.5, 1.6_

- [x] 3.2 Create PostForm Volt component for modal
  - Implement create/edit form with all post fields
  - Add category multi-select with real-time updates
  - Implement slug auto-generation from title
  - Add rich text editor for body content
  - _Requirements: 1.2, 1.3, 1.4, 1.7_

- [x] 3.3 Write property test for post persistence ✅ **DOCUMENTED**
  - **Property 1: Data persistence round-trip**
  - **Validates: Requirements 1.4**
  - Tests created in `tests/Unit/PostPersistencePropertyTest.php`
  - 5 tests with ~55 assertions covering:
    - Post creation with random data (title, slug, body, description, featured_image, tags, published_at)
    - Post updates with data integrity verification
    - Null optional fields handling
    - Automatic timestamp management (created_at, updated_at)
    - JSON array field serialization (tags)
    - Model accessor handling for default values
    - Global scope bypass for unpublished posts
  - **Documentation**: `tests/Unit/POST_PERSISTENCE_PROPERTY_TESTING.md`
  - **Quick Reference**: `tests/Unit/POST_PERSISTENCE_QUICK_REFERENCE.md`

- [x] 3.4 Write property test for post deletion
  - **Property 2: Deletion removes resource**
  - **Validates: Requirements 1.5**

- [x] 3.5 Write property test for publication status toggle
  - **Property 27: Publication status toggle**
  - **Validates: Requirements 1.6**

- [x] 3.6 Write property test for category relationship sync
  - **Property 3: Relationship synchronization**
  - **Validates: Requirements 1.7, 11.3**

- [x] 3.7 Write property test for category badge display
  - **Property 33: Category badge display**
  - **Validates: Requirements 1.7, 11.4**

- [x] 3.8 Write property test for category isolation
  - **Property 3: Relationship synchronization** (isolation aspect)
  - **Validates: Requirements 11.5**

- [x] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implement Comments CRUD with Livewire
  - Build comments management with status filtering and bulk actions
  - Includes inline editing and approval workflow
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_

- [x] 5.1 Create CommentsIndex Volt component
  - Implement index with search, status filter, sorting, and pagination
  - Add bulk selection with ManagesBulkActions trait
  - Implement status change actions (approve, reject)
  - Add delete functionality with post count update
  - _Requirements: 3.1, 3.2, 3.4, 3.5, 3.6_

- [x] 5.2 Create CommentRow component for inline editing
  - Implement inline content editing
  - Add save and cancel functionality
  - Include validation for inline edits
  - _Requirements: 3.3_

- [x] 5.3 Write property test for comment status filtering ✅ **DOCUMENTED**
  - **Property 11: Status filter accuracy**
  - **Validates: Requirements 3.2**
  - Tests created in `tests/Unit/CommentStatusFilterPropertyTest.php`
  - 3 tests with ~495 assertions covering:
    - Status filter returns only matching comments (Approved, Pending, Rejected)
    - withStatus scope filters correctly with enum values
    - Empty results return empty collection (not null)
    - All status values tested with random distributions
    - Edge cases: empty database, no matches
  - **Documentation**: `tests/Unit/COMMENT_STATUS_FILTER_TESTING.md`
  - **Quick Reference**: `tests/Unit/COMMENT_STATUS_FILTER_QUICK_REFERENCE.md`
  - **Test Coverage**: Updated in `docs/TEST_COVERAGE.md`

- [x] 5.4 Write property test for inline edit persistence ✅ **DOCUMENTED**
  - **Property 1: Data persistence round-trip** (inline edit aspect)
  - **Validates: Requirements 3.3**
  - Tests created in `tests/Unit/CommentInlineEditPropertyTest.php`
  - 5 tests with ~1,100 assertions covering:
    - Inline edit content persistence (100 iterations, ~300 assertions)
    - Inline edit status persistence (100 iterations, ~200 assertions)
    - Multiple sequential inline edits (50 × 3 edits, ~300 assertions)
    - Empty content edge case (50 iterations, ~100 assertions)
    - Timestamp updates (100 iterations, ~200 assertions)
  - **Documentation**: `tests/Unit/COMMENT_INLINE_EDIT_PROPERTY_TESTING.md`
  - **Quick Reference**: `tests/Unit/COMMENT_INLINE_EDIT_QUICK_REFERENCE.md`

- [ ]* 5.5 Write property test for comment status update
  - **Property 28: Comment status update**
  - **Validates: Requirements 3.4**
  - Note: Requires using Comment::factory() to avoid password hashing issues

- [ ]* 5.6 Write property test for comment deletion count update
  - **Property 32: Comment deletion count update**
  - **Validates: Requirements 3.5**
  - Note: Requires using Comment::factory() to avoid password hashing issues

- [ ]* 5.7 Write property test for bulk actions
  - **Property 19: Bulk operation completeness**
  - **Validates: Requirements 3.6, 8.3**
  - Note: Requires using Comment::factory() to avoid password hashing issues

- [x] 6. Implement Users CRUD with Livewire
  - Build users management with role and ban status controls
  - Includes email uniqueness validation and search
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_

- [x] 6.1 Create UsersIndex Volt component
  - Implement index with search, sorting, and pagination
  - Add role badge display and account status indicators
  - Implement delete functionality with content handling
  - _Requirements: 4.1, 4.5, 4.6_

- [x] 6.2 Create UserForm Volt component for modal
  - Implement create/edit form with email validation
  - Add role toggle switches (is_admin, is_author)
  - Add ban status toggle
  - _Requirements: 4.2, 4.3, 4.4_

- [ ]* 6.3 Write property test for email uniqueness
  - **Property 9: Email uniqueness validation**
  - **Validates: Requirements 4.2**

- [ ]* 6.4 Write property test for user role updates
  - **Property 29: User role flag updates**
  - **Validates: Requirements 4.3**

- [ ]* 6.5 Write property test for user ban status
  - **Property 30: User ban status update**
  - **Validates: Requirements 4.4**

- [ ]* 6.6 Write property test for user deletion
  - **Property 2: Deletion removes resource** (user aspect)
  - **Validates: Requirements 4.5**

- [ ]* 6.7 Write property test for user search
  - **Property 10: Search filtering accuracy** (user aspect)
  - **Validates: Requirements 4.6**

- [x] 7. Implement inline editing functionality
  - Add inline editing capabilities to all resource tables
  - Includes edit mode toggle, validation, and cancellation
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 7.1 Implement inline editing in Categories and Comments
  - Categories has inline editing for name and slug fields
  - Comments has inline editing for content and status
  - Both include save/cancel functionality and validation
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ]* 7.2 Write property test for inline edit cancellation
  - **Property 21: Inline edit cancellation preservation**
  - **Validates: Requirements 5.4**

- [ ]* 7.3 Write property test for inline validation
  - **Property 4: Invalid input rejection** (inline aspect)
  - **Validates: Requirements 5.2, 5.5**

- [x] 8. Implement modal workflows
  - Modal behavior for create/edit operations implemented
  - Includes form state management and validation handling
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 8.1 Modal workflows implemented across all resources
  - Categories uses side-panel form with open/close state
  - Posts uses Flux modal component with form state reset
  - Users uses custom modal with create/delete workflows
  - All modals handle validation errors and state management
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ]* 8.2 Write property test for modal form reset
  - **Property 22: Modal form reset on close**
  - **Validates: Requirements 6.5**

- [ ]* 8.3 Write property test for modal validation persistence
  - **Property 23: Modal persistence on validation error**
  - **Validates: Requirements 6.3**

- [ ]* 8.4 Write property test for successful modal save
  - **Property 24: Successful save modal closure**
  - **Validates: Requirements 6.4**

- [-] 9. Implement advanced filtering and search
  - Combined filter support and URL state management implemented
  - Includes filter reset and bookmarkable URLs
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 9.1 Advanced filtering implemented across resources
  - Posts has status and author filters with search
  - Comments has status filter
  - Categories has search with sorting
  - Users has search functionality
  - All use query string persistence for bookmarkable URLs
  - Filter clear functionality implemented
  - _Requirements: 7.2, 7.3, 7.4, 7.5_

- [ ]* 9.2 Write property test for combined filters
  - **Property 12: Combined filter intersection**
  - **Validates: Requirements 7.3**

- [ ]* 9.3 Write property test for filter reset
  - **Property 13: Filter reset completeness**
  - **Validates: Requirements 7.4**

- [ ]* 9.4 Write property test for URL persistence
  - **Property 14: URL query string persistence**
  - **Validates: Requirements 7.5, 9.4**

- [ ]* 9.5 Write property test for sort state restoration
  - **Property 16: Sort state restoration**
  - **Validates: Requirements 9.5**

- [ ] 10. Implement bulk actions
  - Bulk action support implemented for Posts and Comments
  - Includes selection management and error handling
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 10.1 Bulk actions implemented in Posts and Comments
  - Posts has bulk publish/unpublish with selection UI
  - Comments has bulk approve/reject/delete with selection UI
  - Both show success/error messaging with counts
  - Partial failures handled with detailed error reporting
  - Selection state managed with checkboxes and select-all
  - _Requirements: 8.1, 8.3, 8.4, 8.5_

- [ ] 10.2 Write property test for bulk selection display
  - **Property 17: Bulk selection accuracy**
  - **Validates: Requirements 8.1, 8.4**

- [ ] 10.3 Write property test for bulk operation success
  - **Property 19: Bulk operation completeness**
  - **Validates: Requirements 8.3, 8.4**

- [ ] 10.4 Write property test for bulk partial failure
  - **Property 20: Bulk operation partial failure reporting**
  - **Validates: Requirements 8.5**

- [ ] 11. Implement comprehensive validation
  - Real-time validation with error clearing implemented
  - Includes format hints and server-side error display
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [ ] 11.1 All forms have real-time validation
  - Categories form validates name, slug format, and uniqueness
  - Posts form validates all fields with live feedback
  - Comments inline edit validates content
  - Users form validates email uniqueness and password
  - Validation errors display adjacent to fields
  - Errors clear on field correction with live updates
  - Format hints provided (e.g., slug format, email format)
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [ ] 11.2 Write property test for validation error display
  - **Property 4: Invalid input rejection**
  - **Validates: Requirements 10.1, 10.4**

- [ ] 11.3 Write property test for error clearing
  - **Property 5: Error clearing on correction**
  - **Validates: Requirements 10.2**

- [ ] 11.4 Write property test for validation success
  - **Property 6: Validation success enables submission**
  - **Validates: Requirements 10.5**

- [ ] 12. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 13. Enhance optimistic UI updates
  - Add more sophisticated optimistic updates for user actions
  - Includes loading indicators and error reversion
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

- [ ] 13.1 Add enhanced optimistic update logic
  - Livewire already provides basic optimistic updates via wire:loading
  - Add explicit loading indicators for actions exceeding 500ms
  - Implement reversion logic for failed actions with error messages
  - Add action queue for sequential processing where needed
  - Consider using wire:loading.delay.500ms for latency indicators
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

- [ ] 13.2 Write property test for optimistic UI immediate update
  - **Property 34: Optimistic UI immediate update**
  - **Validates: Requirements 12.1**

- [ ] 13.3 Write property test for optimistic UI persistence
  - **Property 35: Optimistic UI persistence on success**
  - **Validates: Requirements 12.2**

- [ ] 13.4 Write property test for optimistic UI reversion
  - **Property 36: Optimistic UI reversion on failure**
  - **Validates: Requirements 12.3**

- [ ] 13.5 Write property test for loading indicators
  - **Property 37: Loading indicator display on latency**
  - **Validates: Requirements 12.4**

- [ ] 13.6 Write property test for sequential processing
  - **Property 38: Sequential action processing**
  - **Validates: Requirements 12.5**

- [ ] 14. UI components and styling
  - Reusable UI components implemented for admin interface
  - Consistent styling and accessibility features in place
  - _Requirements: All visual requirements_

- [ ] 14.1 Admin UI components implemented
  - Data table component with sorting indicators (x-admin.table)
  - Badge components for status and roles (flux:badge)
  - Empty state components (x-admin.table-empty)
  - Loading states with wire:loading
  - Consistent styling across all admin pages
  - _Requirements: 1.1, 2.1, 3.1, 4.1, 9.3_

- [ ] 14.2 Enhance accessibility features
  - Review keyboard navigation for all interactions
  - Audit ARIA labels for screen readers
  - Verify focus management for modals
  - Add skip links for table navigation if needed
  - _Requirements: All requirements_

- [ ] 15. Configuration
  - Configuration already exists in config/interface.php
  - Per-page defaults configured via PageSize class
  - _Requirements: All requirements_

- [ ] 15.1 Review and enhance admin configuration
  - Verify per-page defaults and options in config/interface.php
  - Review debounce timing (currently 300ms)
  - Consider adding bulk action limits if needed
  - Document configuration options
  - _Requirements: 7.1, 8.3_

- [ ] 15.2 Add comprehensive code documentation
  - Add PHPDoc blocks to all Volt components
  - Document trait methods and properties
  - Add inline comments for complex logic
  - Document configuration options
  - _Requirements: All requirements_

- [ ] 16. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

