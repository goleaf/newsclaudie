# Comment Model - Database Architecture Documentation

**Version**: 3.0  
**Last Updated**: 2025-11-23  
**Status**: Production Ready

---

## 📊 Database Schema

### Table: `comments`

| Column | Type | Nullable | Default | Indexed | Description |
|--------|------|----------|---------|---------|-------------|
| `id` | bigint unsigned | NO | AUTO_INCREMENT | PRIMARY | Unique identifier |
| `user_id` | bigint unsigned | NO | - | YES | Foreign key to users table |
| `post_id` | bigint unsigned | NO | - | YES | Foreign key to posts table |
| `content` | text | NO | - | NO | Comment content (up to 65,535 chars) |
| `status` | varchar(20) | NO | 'pending' | YES | Comment status (pending/approved/rejected) |
| `ip_address` | varchar(45) | YES | NULL | YES | IPv4/IPv6 address for spam detection |
| `user_agent` | varchar(500) | YES | NULL | NO | Browser user agent string |
| `approved_at` | timestamp | YES | NULL | NO | When comment was approved |
| `approved_by` | bigint unsigned | YES | NULL | NO | Foreign key to users (who approved) |
| `created_at` | timestamp | YES | NULL | NO | Record creation timestamp |
| `updated_at` | timestamp | YES | NULL | NO | Record update timestamp |
| `deleted_at` | timestamp | YES | NULL | YES | Soft delete timestamp |

---

## 🔑 Indexes

### Primary Key
- `PRIMARY KEY (id)`

### Foreign Keys
- `comments_user_id_foreign` → `users(id)` ON DELETE CASCADE
- `comments_post_id_foreign` → `posts(id)` ON DELETE CASCADE
- `comments_approved_by_foreign` → `users(id)` ON DELETE SET NULL

### Composite Indexes (Performance Optimized)
- `comments_status_created_at_index` (status, created_at)
  - **Use Case**: `Comment::approved()->latest()`
  - **Benefit**: Fast filtering by status with date ordering
  
- `comments_post_id_status_index` (post_id, status)
  - **Use Case**: `$post->comments()->approved()`
  - **Benefit**: Fast post comment retrieval with status filter
  
- `comments_user_id_created_at_index` (user_id, created_at)
  - **Use Case**: User comment history queries
  - **Benefit**: Fast user comment retrieval with date ordering

### Single Column Indexes
- `comments_deleted_at_index` (deleted_at)
  - **Use Case**: Soft delete queries
  - **Benefit**: Fast trashed/withTrashed queries
  
- `comments_ip_address_index` (ip_address)
  - **Use Case**: Spam detection and IP-based filtering
  - **Benefit**: Fast IP lookup for moderation

---

## 🔄 Relationships

### Belongs To
- **User** (`user_id` → `users.id`)
  - The author of the comment
  - Cascade delete: When user is deleted, their comments are deleted
  
- **Post** (`post_id` → `posts.id`)
  - The post this comment belongs to
  - Cascade delete: When post is deleted, its comments are deleted
  
- **Approver** (`approved_by` → `users.id`)
  - The admin who approved the comment
  - Set null on delete: If approver is deleted, comment remains but approver is null

### Has Many (Inverse)
- **User** has many **Comments** (`comments.user_id`)
- **Post** has many **Comments** (`comments.post_id`)

---

## 📈 Data Types & Constraints

### Content Field
- **Type**: `TEXT` (65,535 bytes)
- **Rationale**: User comments can be lengthy; string(1024) was too restrictive
- **Validation**: Should be validated at application level (min 3 chars, max reasonable limit)

### IP Address Field
- **Type**: `VARCHAR(45)`
- **Rationale**: IPv6 addresses can be up to 45 characters (e.g., `2001:0db8:85a3:0000:0000:8a2e:0370:7334`)
- **Privacy**: Use `masked_ip` accessor for display to comply with privacy regulations

### User Agent Field
- **Type**: `VARCHAR(500)`
- **Rationale**: Modern user agent strings can be quite long (200-400 chars typical)
- **Use Case**: Bot detection, analytics, debugging

### Status Field
- **Type**: `VARCHAR(20)` with enum backing
- **Values**: `pending`, `approved`, `rejected`
- **Default**: `pending`
- **Indexed**: Yes, for fast status filtering

---

## 🎯 Query Optimization Patterns

### Pattern 1: Get Approved Comments for a Post
```php
// ✅ Optimized (uses comments_post_id_status_index)
$comments = Comment::forPost($post)
    ->approved()
    ->latest()
    ->get();

// Query: SELECT * FROM comments 
//        WHERE post_id = ? AND status = 'approved' AND deleted_at IS NULL
//        ORDER BY created_at DESC
// Index Used: comments_post_id_status_index
```

### Pattern 2: Get Recent Approved Comments
```php
// ✅ Optimized (uses comments_status_created_at_index)
$comments = Comment::approved()
    ->latest()
    ->limit(10)
    ->get();

// Query: SELECT * FROM comments 
//        WHERE status = 'approved' AND deleted_at IS NULL
//        ORDER BY created_at DESC LIMIT 10
// Index Used: comments_status_created_at_index
```

### Pattern 3: Get User's Comment History
```php
// ✅ Optimized (uses comments_user_id_created_at_index)
$comments = Comment::byUser($userId)
    ->latest()
    ->paginate(20);

// Query: SELECT * FROM comments 
//        WHERE user_id = ? AND deleted_at IS NULL
//        ORDER BY created_at DESC LIMIT 20 OFFSET ?
// Index Used: comments_user_id_created_at_index
```

### Pattern 4: Spam Detection by IP
```php
// ✅ Optimized (uses comments_ip_address_index)
$suspiciousComments = Comment::fromIp($ipAddress)
    ->where('created_at', '>', now()->subHour())
    ->count();

// Query: SELECT COUNT(*) FROM comments 
//        WHERE ip_address = ? AND created_at > ? AND deleted_at IS NULL
// Index Used: comments_ip_address_index
```

---

## 🔒 Data Integrity & Constraints

### Cascade Delete Rules

1. **User Deletion** → Comments are deleted
   - **Rationale**: Comments without authors are meaningless
   - **Implementation**: `ON DELETE CASCADE` on `user_id`
   - **Impact**: When a user is deleted, all their comments are automatically removed

2. **Post Deletion** → Comments are deleted
   - **Rationale**: Comments without posts are orphaned
   - **Implementation**: `ON DELETE CASCADE` on `post_id`
   - **Impact**: When a post is deleted, all its comments are automatically removed

3. **Approver Deletion** → Approved_by set to NULL
   - **Rationale**: Approval history is valuable even if approver leaves
   - **Implementation**: `ON DELETE SET NULL` on `approved_by`
   - **Impact**: Comment remains approved, but approver reference is cleared

### Soft Delete Behavior

- **Default Queries**: Exclude soft-deleted comments
- **Explicit Inclusion**: Use `withTrashed()` or `onlyTrashed()`
- **Restoration**: Use `restore()` to undelete
- **Permanent Deletion**: Use `forceDelete()` for GDPR compliance

---

## 🛡️ Security Considerations

### IP Address Storage
- **Purpose**: Spam detection, rate limiting, abuse prevention
- **Privacy**: Use `masked_ip` accessor for display
- **Compliance**: Consider GDPR/CCPA requirements for IP storage
- **Retention**: Implement data retention policy (e.g., delete after 90 days)

### User Agent Storage
- **Purpose**: Bot detection, analytics
- **Privacy**: Generally considered non-PII but be cautious
- **Use Case**: Identify automated spam submissions

### Content Validation
- **XSS Prevention**: Always escape output in views
- **SQL Injection**: Use Eloquent ORM (parameterized queries)
- **Length Limits**: Validate at application level
- **Spam Detection**: Use `isPotentialSpam()` method

---

## 📊 Performance Benchmarks

### Expected Query Performance (10,000 comments)

| Query Type | Without Index | With Index | Improvement |
|------------|---------------|------------|-------------|
| Status filter + order | ~50ms | ~2ms | 25x faster |
| Post comments by status | ~40ms | ~1ms | 40x faster |
| User comment history | ~35ms | ~1.5ms | 23x faster |
| IP-based lookup | ~45ms | ~2ms | 22x faster |

### Scaling Considerations

- **Up to 100K comments**: Current indexes sufficient
- **100K - 1M comments**: Consider partitioning by date
- **1M+ comments**: Consider archiving old comments, read replicas

---

## 🔄 Migration History

1. **2022-02-18**: Initial `comments` table creation
2. **2025-11-23**: Added soft deletes with index
3. **2025-11-23**: Added composite indexes for performance
4. **2025-11-23**: Added `status` column with enum
5. **2025-11-23**: Changed `content` from varchar(1024) to text
6. **2025-11-23**: Added metadata columns (ip_address, user_agent, approved_at, approved_by)
7. **2025-11-23**: Added foreign key constraints with cascade rules

---

## 🧪 Testing Recommendations

### Property-Based Tests
- Status transitions are idempotent
- Soft deletes preserve data integrity
- Cascade deletes work correctly
- Indexes improve query performance

### Integration Tests
- Comment creation with IP/user agent tracking
- Approval workflow with approver tracking
- Spam detection accuracy
- Bulk operations performance

### Load Tests
- 10K+ comments per post
- Concurrent approval operations
- IP-based rate limiting

---

## 📚 Related Documentation

- **Quick Reference**: `COMMENT_MODEL_QUICK_REFERENCE.md`
- **Refactoring Summary**: `REFACTORING_COMMENT_MODEL.md`
- **API Documentation**: `../api/COMMENTS_API.md`
- **Testing Guide**: `tests/Unit/Comment*PropertyTest.php`

---

## 🔮 Future Enhancements

### Potential Improvements
1. **Nested Comments**: Add `parent_id` for threaded discussions
2. **Reactions**: Add likes/dislikes with separate table
3. **Mentions**: Add user mentions with notifications
4. **Rich Text**: Support markdown or HTML content
5. **Attachments**: Allow image/file uploads
6. **Moderation Queue**: Dedicated table for moderation workflow
7. **Spam Score**: ML-based spam detection score
8. **Edit History**: Track comment edits with separate table

### Database Optimizations
1. **Full-Text Search**: Add FULLTEXT index on content
2. **Materialized Views**: For comment counts per post
3. **Caching Layer**: Redis for frequently accessed comments
4. **Archive Strategy**: Move old comments to archive table

---

**Maintained by**: Development Team  
**Review Cycle**: Quarterly  
**Next Review**: 2025-02-23
