<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Support\Pagination\PageSize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    private const CATEGORY_PAGE_SIZE_DEFAULT = 15;

    /**
     * @var array<int>
     */
    private const CATEGORY_PAGE_SIZE_OPTIONS = [12, 15, 24, 30];

    private const CATEGORY_POST_PAGE_SIZE_DEFAULT = 12;

    /**
     * @var array<int>
     */
    private const CATEGORY_POST_PAGE_SIZE_OPTIONS = [9, 12, 18, 24];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $categoryPageSizeOptions = PageSize::options(
            self::CATEGORY_PAGE_SIZE_OPTIONS,
            self::CATEGORY_PAGE_SIZE_DEFAULT,
        );
        $perPageParam = PageSize::queryParam();

        $perPage = PageSize::resolve(
            $request->integer($perPageParam),
            $categoryPageSizeOptions,
            self::CATEGORY_PAGE_SIZE_DEFAULT,
        );

        $categories = Category::withCount('posts')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('categories.index', [
            'categories' => $categories,
            'categoryPageSizeOptions' => $categoryPageSizeOptions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return redirect()->route('categories.index')
            ->with('success', __('messages.category_created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Category $category): View
    {
        $postPageSizeOptions = PageSize::options(
            self::CATEGORY_POST_PAGE_SIZE_OPTIONS,
            self::CATEGORY_POST_PAGE_SIZE_DEFAULT,
        );
        $perPageParam = PageSize::queryParam();

        $perPage = PageSize::resolve(
            $request->integer($perPageParam),
            $postPageSizeOptions,
            self::CATEGORY_POST_PAGE_SIZE_DEFAULT,
        );

        $posts = $category->posts()
            ->with('author')
            ->withCount('comments')
            ->paginate($perPage)
            ->withQueryString();

        return view('categories.show', [
            'category' => $category,
            'posts' => $posts,
            'categoryPostPageSizeOptions' => $postPageSizeOptions,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')
            ->with('success', __('messages.category_updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', __('messages.category_deleted'));
    }
}
