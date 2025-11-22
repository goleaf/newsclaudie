<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('News') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Filter Section --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ route('news.index') }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            {{-- Categories Filter --}}
                            <div>
                                <label for="categories" class="block text-sm font-medium text-gray-700">Categories</label>
                                <select name="categories[]" id="categories" multiple class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" 
                                            {{ in_array($category->id, $appliedFilters['categories'] ?? []) ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Authors Filter --}}
                            <div>
                                <label for="authors" class="block text-sm font-medium text-gray-700">Authors</label>
                                <select name="authors[]" id="authors" multiple class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    @foreach($authors as $author)
                                        <option value="{{ $author->id }}"
                                            {{ in_array($author->id, $appliedFilters['authors'] ?? []) ? 'selected' : '' }}>
                                            {{ $author->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Date Range --}}
                            <div>
                                <label for="from_date" class="block text-sm font-medium text-gray-700">From Date</label>
                                <input type="date" name="from_date" id="from_date" 
                                    value="{{ $appliedFilters['from_date'] ?? '' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            <div>
                                <label for="to_date" class="block text-sm font-medium text-gray-700">To Date</label>
                                <input type="date" name="to_date" id="to_date"
                                    value="{{ $appliedFilters['to_date'] ?? '' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <div>
                                <label for="sort" class="block text-sm font-medium text-gray-700">Sort By</label>
                                <select name="sort" id="sort" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="newest" {{ ($appliedFilters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' }}>
                                        Newest First
                                    </option>
                                    <option value="oldest" {{ ($appliedFilters['sort'] ?? 'newest') === 'oldest' ? 'selected' : '' }}>
                                        Oldest First
                                    </option>
                                </select>
                            </div>

                            <div class="flex gap-2">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                    Apply Filters
                                </button>
                                <a href="{{ route('news.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                    Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Results Count --}}
            <div class="mb-4 text-sm text-gray-600">
                Showing {{ $posts->count() }} of {{ $totalCount }} posts
            </div>

            {{-- Posts Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($posts as $post)
                    <article class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-2">
                                <a href="{{ route('posts.show', $post) }}" class="hover:text-blue-600">
                                    {{ $post->title }}
                                </a>
                            </h3>

                            <div class="text-sm text-gray-600 mb-2">
                                By {{ $post->author->name }} on {{ $post->published_at->format('M d, Y') }}
                            </div>

                            @if($post->categories->isNotEmpty())
                                <div class="flex flex-wrap gap-2 mb-3">
                                    @foreach($post->categories as $category)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $category->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <p class="text-gray-700 text-sm">
                                {{ Str::limit($post->description, 150) }}
                            </p>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-center text-gray-500">
                            No posts found matching your filters.
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $posts->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
