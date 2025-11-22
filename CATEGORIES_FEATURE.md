# Categories Feature Documentation

## Overview
The categories feature allows you to organize blog posts into different categories. Posts can belong to multiple categories, and categories can contain multiple posts (many-to-many relationship).

## Features

### 1. Category Management
- **Create Categories**: Add new categories with name, slug, and description
- **Edit Categories**: Update existing category information
- **Delete Categories**: Remove categories (posts remain unaffected)
- **List Categories**: View all categories with post counts

### 2. Post-Category Association
- **Assign Categories**: Select multiple categories when creating/editing posts
- **View Posts by Category**: Browse all posts in a specific category
- **Category Badges**: Display category information on post pages

## Usage

### Creating a Category

1. Navigate to `/categories`
2. Click "Create Category" button
3. Fill in the form:
   - **Name**: The display name (e.g., "Technology")
   - **Slug**: URL-friendly identifier (auto-generated from name)
   - **Description**: Optional description of the category
4. Click "Create Category"

The slug is automatically generated as you type the name, but you can customize it if needed.

### Assigning Categories to Posts

When creating or editing a post:

1. Scroll to the "Categories" section in the form
2. Check the boxes next to the categories you want to assign
3. Save the post

The post will now appear in all selected categories.

### Viewing Posts by Category

1. Navigate to `/categories` to see all categories
2. Click on a category name or "View Posts" button
3. You'll see all posts assigned to that category

## API Endpoints

### Category Routes
```
GET    /categories              - List all categories
GET    /categories/create       - Show create form
POST   /categories              - Store new category
GET    /categories/{slug}       - Show category and posts
GET    /categories/{slug}/edit  - Show edit form
PUT    /categories/{slug}       - Update category
DELETE /categories/{slug}       - Delete category
```

## Database Schema

### Categories Table
```sql
CREATE TABLE categories (
    id INTEGER PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Category-Post Pivot Table
```sql
CREATE TABLE category_post (
    id INTEGER PRIMARY KEY,
    category_id INTEGER NOT NULL,
    post_id INTEGER NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE (category_id, post_id)
);
```

## Validation Rules

### Category Creation/Update
- **Name**: Required, string, max 255 characters
- **Slug**: Required, string, max 255 characters, unique, must match pattern: `^[a-z0-9]+(?:-[a-z0-9]+)*$`
- **Description**: Optional, string, max 1000 characters

## Code Examples

### Accessing Categories in a Post
```php
$post = Post::find(1);
$categories = $post->categories; // Collection of categories

foreach ($categories as $category) {
    echo $category->name;
}
```

### Accessing Posts in a Category
```php
$category = Category::where('slug', 'technology')->first();
$posts = $category->posts; // Collection of posts

foreach ($posts as $post) {
    echo $post->title;
}
```

### Creating a Category Programmatically
```php
use App\Models\Category;

$category = Category::create([
    'name' => 'Technology',
    'slug' => 'technology',
    'description' => 'Articles about technology and programming',
]);
```

### Assigning Categories to a Post
```php
$post = Post::find(1);
$categoryIds = [1, 2, 3]; // Category IDs

$post->categories()->sync($categoryIds);
```

## Seeding Data

To seed sample categories:

```bash
php artisan db:seed --class=CategorySeeder
```

This will create:
- 5 predefined categories (Technology, Lifestyle, Business, Travel, Food)
- 5 random categories

## Testing

Run category tests:

```bash
php artisan test --filter=CategoryControllerTest
```

All tests should pass:
- ✓ Categories index page can be rendered
- ✓ Categories index displays categories
- ✓ Category show page displays category and posts
- ✓ Category can be created
- ✓ Category can be updated
- ✓ Category can be deleted
- ✓ Category slug must be unique
- ✓ Category name is required
- ✓ Category slug is required

## Customization

### Changing Category Display
Edit `resources/views/categories/index.blade.php` to customize how categories are displayed.

### Adding Category Icons
1. Add an `icon` field to the categories table
2. Update the CategoryRequest validation
3. Update the category forms to include icon selection
4. Display icons in the category views

### Category Permissions
To restrict category management to certain users, update the CategoryController constructor:

```php
public function __construct()
{
    $this->middleware('auth')->except(['index', 'show']);
    $this->middleware('can:manage-categories')->only(['create', 'store', 'edit', 'update', 'destroy']);
}
```

## Troubleshooting

### Categories Not Showing
1. Ensure categories are seeded: `php artisan db:seed --class=CategorySeeder`
2. Check database connection
3. Clear cache: `php artisan cache:clear`

### Slug Already Exists Error
Each category must have a unique slug. Try a different slug or check existing categories.

### Categories Not Saving to Posts
Ensure the category checkboxes have the name attribute `categories[]` in the post forms.

## Future Enhancements
- Category hierarchies (parent/child categories)
- Category images/icons
- Category-specific templates
- Category RSS feeds
- Category analytics
- Category-based permissions

