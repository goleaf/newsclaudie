<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

final class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['show']]);
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

        $post = (new CreatesNewPost())->createPost($request->user(), $validated);

        // Sync categories
        if ($post && ! empty($categories)) {
            $post->categories()->sync($categories);
        }

        return $this->redirectAfterSave($request, $post, __('admin.posts.created'));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post): View
    {
        $this->authorize('view', $post);
        $post->loadMissing(['categories', 'author'])
            ->loadCount([
                'comments as comments_count' => fn ($query) => $query->approved(),
            ]);

        // Generate formatted HTML from markdown
        $markdown = (new MarkdownConverter($post->body))->toHtml();

        return view('post.show', [
            'post' => $post,
            'markdown' => $markdown,
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

        return $this->redirectAfterSave($request, $post, __('admin.posts.updated'));
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

        return $this->redirectAfterDelete($request, __('admin.posts.deleted'));
    }

    private function redirectAfterSave(Request $request, Post $post, string $message)
    {
        $targetRoute = $request->string('redirect_to')->toString();

        if ($targetRoute !== '' && Route::has($targetRoute)) {
            $route = Route::getRoutes()->getByName($targetRoute);

            if ($route && count($route->parameterNames()) === 0) {
                return redirect()
                    ->route($targetRoute)
                    ->with('status', $message)
                    ->with('success', $message);
            }

            if ($route && in_array('post', $route->parameterNames(), true)) {
                return redirect()
                    ->route($targetRoute, ['post' => $post])
                    ->with('status', $message)
                    ->with('success', $message);
            }
        }

        return redirect()
            ->route('posts.show', ['post' => $post])
            ->with('status', $message)
            ->with('success', $message);
    }

    private function redirectAfterDelete(Request $request, string $message)
    {
        $targetRoute = $request->string('redirect_to')->toString();

        if ($targetRoute !== '' && Route::has($targetRoute)) {
            return redirect()
                ->route($targetRoute)
                ->with('status', $message)
                ->with('success', $message);
        }

        return back()->with('success', $message)->with('status', $message);
    }
}
