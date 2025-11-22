<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PostIndexRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\Pagination\PageSize;
use Illuminate\Http\Request;

final class PostController extends Controller
{
    public const POST_PAGE_SIZE_DEFAULT = 12;

    /**
     * @var array<int>
     */
    public const POST_PAGE_SIZE_OPTIONS = [12, 18, 24, 36];

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the resource with support for filters and pagination.
     */
    public function index(PostIndexRequest $request)
    {
        $filters = $request->validated();
        $perPageParam = PageSize::queryParam();
        $perPageOptions = PageSize::options(self::POST_PAGE_SIZE_OPTIONS, self::POST_PAGE_SIZE_DEFAULT);
        $perPage = PageSize::resolve(
            isset($filters[$perPageParam]) ? (int) $filters[$perPageParam] : null,
            $perPageOptions,
            self::POST_PAGE_SIZE_DEFAULT
        );
        $categories = Category::orderBy('name')->get();

        $query = Post::query()
            ->with(['author'])
            ->withCount('comments');

        $title = __('posts.title');
        $filterLabel = null;
        $activeCategory = null;

        if (! empty($filters['filterByTag'] ?? null)) {
            $tag = $filters['filterByTag'];
            $query->whereJsonContains('tags', $tag);

            $title = 'Posts with '.__('blog.tag').' '.$tag;
            $filterLabel = 'Filtered by '.__('blog.tag').' "'.$tag.'"';
        } elseif (! empty($filters['author'] ?? null)) {
            $author = User::findOrFail($filters['author']);
            $query->where('user_id', $author->id);

            $title = 'Posts by '.$author->name;
            $filterLabel = 'Filtered by author '.$author->name;
        } elseif (! empty($filters['category'] ?? null)) {
            $category = $categories->firstWhere('slug', $filters['category'])
                ?? Category::where('slug', $filters['category'])->firstOrFail();

            $query->whereHas('categories', fn ($builder) => $builder->where('categories.id', $category->id));

            $title = __('posts.filtered_by_category', ['category' => $category->name]);
            $filterLabel = __('posts.category_filter_badge', ['category' => $category->name]);
            $activeCategory = $category;
        }

        $posts = $query
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('post.index', [
            'title' => $title,
            'filter' => $filterLabel,
            'posts' => $posts,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'perPage' => $perPage,
            'postPageSizeOptions' => $perPageOptions,
            'perPageParam' => $perPageParam,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', App\Models\Post::class);

        $categories = Category::orderBy('name')->get();

        if (config('blog.easyMDE.enabled')) {
            if (! $request->has('draft_id')) {
                return redirect(route('posts.create', ['draft_id' => time()]));
            }

            return view('post.create', [
                'draft_id' => $request->get('draft_id'),
                'categories' => $categories,
            ]);
        }

        return view('post.create', compact('categories'));
    }

    /**
     * Store a new blog post.
     *
     * @return Illuminate\Http\Response
     */
    public function store(StorePostRequest $request)
    {
        // The incoming request is valid and authorized...

        // Retrieve the validated input data...
        $validated = $request->validated();

        // Extract categories before creating post
        $categories = $request->input('categories', []);

        // Create the post
        $response = (new CreatesNewPost)->store($request->user(), $validated);

        // Get the created post from the response
        $post = Post::where('slug', $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['title']))->latest()->first();

        // Sync categories
        if ($post && ! empty($categories)) {
            $post->categories()->sync($categories);
        }

        return $response;
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Post $post)
    {
        $this->authorize('view', $post);
        $post->loadMissing(['categories', 'author'])->loadCount('comments');

        $comments = null;
        $commentsPerPage = null;
        $commentPerPageOptions = [];

        if (config('blog.allowComments')) {
            $defaultCommentsPerPage = (int) config('blog.commentsPerPage', 10);
            $commentPerPageOptions = PageSize::options([10, 25, 50, $defaultCommentsPerPage], $defaultCommentsPerPage);
            $commentsPerPage = PageSize::resolve($request->integer('comments_per_page'), $commentPerPageOptions, $defaultCommentsPerPage);

            $comments = $post->comments()
                ->with(['user'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate($commentsPerPage)
                ->withQueryString()
                ->fragment('comments');
        }

        // Generate formatted HTML from markdown
        $markdown = (new MarkdownConverter($post->body))->toHtml();

        return view('post.show', [
            'post' => $post,
            'markdown' => $markdown,
            'comments' => $comments,
            'commentsPerPage' => $commentsPerPage,
            'commentPerPageOptions' => $commentPerPageOptions,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $categories = Category::orderBy('name')->get();

        if (config('blog.easyMDE.enabled')) {
            if (! $request->has('draft_id')) {
                return redirect(route('posts.edit', [
                    'post' => $post,
                    'draft_id' => time(),
                ]));
            }

            return view('post.edit', [
                'post' => $post,
                'draft_id' => $request->get('draft_id'),
                'categories' => $categories,
            ]);
        }

        return view('post.edit', [
            'post' => $post,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        // The incoming request is valid and authorized...

        // Retrieve the validated input data...
        $validated = $request->validated();

        // Extract categories
        $categories = $request->input('categories', []);

        // Update the post
        $post->update($validated);

        // Sync categories
        $post->categories()->sync($categories);

        return redirect()->route('posts.show', ['post' => $post]);
    }

    /**
     * Update the published_at date in the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function publish(Post $post)
    {
        $this->authorize('update', $post);

        $post->published_at = now();
        $post->save();

        return back()->with('success', 'Successfully Published Post!');
    }

    /**
     * Update the published_at date in the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function unpublish(Post $post)
    {
        $this->authorize('update', $post);

        $post->published_at = null;
        $post->save();

        return back()->with('success', 'Successfully Unpublished Post!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return back()->with('success', 'Successfully Deleted Post!');
    }
}
