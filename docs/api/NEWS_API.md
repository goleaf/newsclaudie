# News API Documentation

## Endpoint: GET /news

Public-facing news listing endpoint with filtering and sorting capabilities.

### Base Information

- **URL**: `/news`
- **Method**: `GET`
- **Auth Required**: No
- **Permissions**: Public access

### Query Parameters

| Parameter | Type | Required | Default | Description | Validation |
|-----------|------|----------|---------|-------------|------------|
| `categories[]` | array | No | - | Filter by category IDs (OR logic) | Each ID must exist in categories table |
| `authors[]` | array | No | - | Filter by author/user IDs (OR logic) | Each ID must exist in users table |
| `from_date` | string | No | - | Start date for range filter | Format: Y-m-d (e.g., 2024-01-01) |
| `to_date` | string | No | - | End date for range filter | Format: Y-m-d, must be >= from_date |
| `sort` | string | No | `newest` | Sort order | Values: `newest`, `oldest` |
| `page` | integer | No | `1` | Page number for pagination | Positive integer |

### Request Examples

#### Basic Request (No Filters)

```http
GET /news HTTP/1.1
Host: example.com
Accept: text/html
```

#### Filter by Categories

```http
GET /news?categories[]=1&categories[]=2 HTTP/1.1
Host: example.com
Accept: text/html
```

#### Filter by Authors

```http
GET /news?authors[]=5&authors[]=10 HTTP/1.1
Host: example.com
Accept: text/html
```

#### Filter by Date Range

```http
GET /news?from_date=2024-01-01&to_date=2024-12-31 HTTP/1.1
Host: example.com
Accept: text/html
```

#### Combined Filters with Sorting

```http
GET /news?categories[]=1&authors[]=5&from_date=2024-01-01&sort=oldest HTTP/1.1
Host: example.com
Accept: text/html
```

#### Pagination

```http
GET /news?page=2 HTTP/1.1
Host: example.com
Accept: text/html
```

### Response

#### Success Response (200 OK)

Returns an HTML view with the following data structure:

**View Data:**

```php
[
    'posts' => LengthAwarePaginator {
        'data' => [
            Post {
                'id' => 1,
                'title' => 'Post Title',
                'slug' => 'post-title',
                'excerpt' => 'Post excerpt...',
                'published_at' => '2024-01-15 10:00:00',
                'author' => User {
                    'id' => 5,
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                },
                'categories' => [
                    Category {
                        'id' => 1,
                        'name' => 'Technology',
                        'slug' => 'technology'
                    }
                ]
            },
            // ... more posts
        ],
        'current_page' => 1,
        'per_page' => 15,
        'total' => 45,
        'last_page' => 3
    ],
    'categories' => [
        Category {
            'id' => 1,
            'name' => 'Technology',
            'slug' => 'technology'
        },
        // ... more categories
    ],
    'authors' => [
        User {
            'id' => 5,
            'name' => 'John Doe'
        },
        // ... more authors
    ],
    'totalCount' => 45,
    'appliedFilters' => [
        'categories' => [1, 2],
        'authors' => [5],
        'from_date' => '2024-01-01',
        'to_date' => '2024-12-31',
        'sort' => 'newest'
    ]
]
```

**HTML Response:**

```html
<!DOCTYPE html>
<html>
<head>
    <title>News - Laravel Blog</title>
</head>
<body>
    <h1>News (45 posts)</h1>
    
    <!-- Filter Panel -->
    <form method="GET" action="/news">
        <!-- Category filters -->
        <!-- Author filters -->
        <!-- Date range filters -->
        <!-- Sort options -->
    </form>
    
    <!-- Posts Grid -->
    <div class="posts-grid">
        <article>
            <h2><a href="/posts/post-title">Post Title</a></h2>
            <p>By John Doe on Jan 15, 2024</p>
            <p>Post excerpt...</p>
            <div class="categories">
                <a href="/news?categories[]=1">Technology</a>
            </div>
        </article>
        <!-- More posts... -->
    </div>
    
    <!-- Pagination -->
    <nav>
        <a href="/news?page=1">1</a>
        <a href="/news?page=2">2</a>
        <a href="/news?page=3">3</a>
    </nav>
</body>
</html>
```

### Error Responses

#### 422 Unprocessable Entity (Validation Error)

Returned when query parameters fail validation.

**Example: Invalid Category ID**

```http
GET /news?categories[]=999 HTTP/1.1
```

**Response:**

```json
{
    "message": "The selected categories.0 is invalid.",
    "errors": {
        "categories.0": [
            "The selected categories.0 is invalid."
        ]
    }
}
```

**Example: Invalid Date Format**

```http
GET /news?from_date=2024/01/01 HTTP/1.1
```

**Response:**

```json
{
    "message": "The from date does not match the format Y-m-d.",
    "errors": {
        "from_date": [
            "The from date does not match the format Y-m-d."
        ]
    }
}
```

**Example: Invalid Date Range**

```http
GET /news?from_date=2024-12-31&to_date=2024-01-01 HTTP/1.1
```

**Response:**

```json
{
    "message": "The to date must be a date after or equal to from date.",
    "errors": {
        "to_date": [
            "The to date must be a date after or equal to from date."
        ]
    }
}
```

**Example: Invalid Sort Value**

```http
GET /news?sort=invalid HTTP/1.1
```

**Response:**

```json
{
    "message": "The selected sort is invalid.",
    "errors": {
        "sort": [
            "The selected sort is invalid."
        ]
    }
}
```

### Filter Logic

#### Category Filtering (OR Logic)

When multiple categories are selected, posts matching **ANY** of the categories are returned.

**Example:**
```
GET /news?categories[]=1&categories[]=2
```

Returns posts that belong to:
- Category 1 **OR**
- Category 2

#### Author Filtering (OR Logic)

When multiple authors are selected, posts by **ANY** of the authors are returned.

**Example:**
```
GET /news?authors[]=5&authors[]=10
```

Returns posts authored by:
- User 5 **OR**
- User 10

#### Combined Filters (AND Logic)

When different filter types are combined, they are applied with **AND** logic.

**Example:**
```
GET /news?categories[]=1&authors[]=5&from_date=2024-01-01
```

Returns posts that:
- Belong to category 1 **AND**
- Are authored by user 5 **AND**
- Were published on or after 2024-01-01

#### Date Range Filtering

- `from_date` only: Posts published on or after the specified date
- `to_date` only: Posts published on or before the specified date
- Both: Posts published within the range (inclusive)

### Pagination

- **Items Per Page**: 15
- **Query String Preservation**: All filter parameters are preserved in pagination links
- **Page Parameter**: Use `page` query parameter to navigate pages

**Example:**
```
GET /news?categories[]=1&page=2
```

Returns page 2 of posts in category 1, with the category filter preserved in pagination links.

### Sorting

#### Newest First (Default)

```
GET /news
GET /news?sort=newest
```

Posts are sorted by `published_at` in descending order (most recent first).

#### Oldest First

```
GET /news?sort=oldest
```

Posts are sorted by `published_at` in ascending order (oldest first).

### Performance Notes

1. **Eager Loading**: The endpoint uses eager loading for `author` and `categories` relationships to prevent N+1 queries.

2. **Query Optimization**: 
   - Total count is calculated before pagination
   - Only published posts are queried (published_at IS NOT NULL AND published_at <= NOW())
   - Filter options only include categories/authors with published posts

3. **Database Indexes**: Ensure the following indexes exist for optimal performance:
   - `posts.published_at`
   - `posts.user_id`
   - `category_post.post_id`
   - `category_post.category_id`

### Rate Limiting

This endpoint is subject to standard web rate limiting:
- **Limit**: 60 requests per minute per IP
- **Headers**: 
  - `X-RateLimit-Limit`: Maximum requests allowed
  - `X-RateLimit-Remaining`: Remaining requests
  - `Retry-After`: Seconds until rate limit resets (when exceeded)

### Caching

- **Response Caching**: Not implemented by default
- **Filter Options**: Consider caching categories and authors lists if they don't change frequently
- **Cache Key Example**: `news_filters_{hash_of_params}`

### Security Considerations

1. **SQL Injection**: Protected by Laravel's query builder and parameter binding
2. **XSS**: All output is escaped in Blade templates
3. **CSRF**: Not required for GET requests
4. **Mass Assignment**: Not applicable (read-only endpoint)

### Related Endpoints

- `GET /posts/{post}` - View individual post detail
- `GET /categories/{category}` - View posts in a specific category
- `GET /authors/{author}` - View posts by a specific author (if implemented)

### Code Examples

#### JavaScript/Fetch

```javascript
// Fetch news with filters
async function fetchNews(filters = {}) {
    const params = new URLSearchParams();
    
    if (filters.categories) {
        filters.categories.forEach(id => params.append('categories[]', id));
    }
    
    if (filters.authors) {
        filters.authors.forEach(id => params.append('authors[]', id));
    }
    
    if (filters.from_date) {
        params.append('from_date', filters.from_date);
    }
    
    if (filters.to_date) {
        params.append('to_date', filters.to_date);
    }
    
    if (filters.sort) {
        params.append('sort', filters.sort);
    }
    
    if (filters.page) {
        params.append('page', filters.page);
    }
    
    const response = await fetch(`/news?${params.toString()}`);
    return await response.text(); // Returns HTML
}

// Example usage
const html = await fetchNews({
    categories: [1, 2],
    authors: [5],
    from_date: '2024-01-01',
    sort: 'oldest',
    page: 1
});
```

#### cURL

```bash
# Basic request
curl -X GET "https://example.com/news"

# With filters
curl -X GET "https://example.com/news?categories[]=1&categories[]=2&authors[]=5&from_date=2024-01-01&sort=oldest"

# With pagination
curl -X GET "https://example.com/news?page=2"
```

#### PHP/Guzzle

```php
use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'https://example.com']);

$response = $client->get('/news', [
    'query' => [
        'categories' => [1, 2],
        'authors' => [5],
        'from_date' => '2024-01-01',
        'to_date' => '2024-12-31',
        'sort' => 'oldest',
        'page' => 1
    ]
]);

$html = $response->getBody()->getContents();
```

### Testing

#### Feature Test Example

```php
public function test_news_endpoint_returns_filtered_posts(): void
{
    $category = Category::factory()->create();
    $post = Post::factory()->published()->create();
    $post->categories()->attach($category);

    $response = $this->get(route('news.index', [
        'categories' => [$category->id]
    ]));

    $response->assertOk();
    $response->assertSee($post->title);
}
```

---

**API Version**: 1.0.0  
**Last Updated**: 2024-11-23  
**Endpoint Status**: Stable  
**Breaking Changes**: None planned
