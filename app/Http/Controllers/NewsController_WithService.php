<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\NewsIndexRequest;
use App\Services\NewsFilterService;
use Illuminate\View\View;

/**
 * Controller for the news listing page.
 *
 * Delegates filtering logic to NewsFilterService for better separation of concerns.
 */
final class NewsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly NewsFilterService $filterService
    ) {
    }

    /**
     * Display the news page with filterable published posts.
     *
     * Provides a filterable and sortable view of all published posts with:
     * - Category filtering (OR logic)
     * - Author filtering (OR logic)
     * - Date range filtering
     * - Sort by publication date (newest/oldest)
     * - Pagination with query string preservation
     *
     * @param NewsIndexRequest $request Validated request with filter parameters
     * @return View News index view with posts and filter options
     */
    public function index(NewsIndexRequest $request): View
    {
        $validated = $request->validated();

        // Get filtered posts and count
        $result = $this->filterService->getFilteredPosts($validated);

        // Get filter options for UI
        $filterOptions = $this->filterService->getFilterOptions();

        return view('news.index', [
            'posts' => $result['posts'],
            'totalCount' => $result['totalCount'],
            'categories' => $filterOptions['categories'],
            'authors' => $filterOptions['authors'],
            'appliedFilters' => $validated,
        ]);
    }
}
