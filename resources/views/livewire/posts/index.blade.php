<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\Pagination\PageSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('layouts.app');

new class extends Component {
    use WithPagination;
    use ManagesPerPage;

    private const PAGE_SIZE_CONTEXT = 'posts';

    public ?string $category = null;
    public ?string $filterByTag = null;
    public ?int $author = null;

    protected array $queryString = [
        'category' => ['except' => ''],
        'filterByTag' => ['except' => ''],
        'author' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    protected function perPageContext(): string
    {
        return self::PAGE_SIZE_CONTEXT;
    }

    public function mount(Request $request): void
    {
        $perPageParam = PageSize::queryParam();
        $this->queryString['perPage'] = ['as' => $perPageParam, 'except' => null];

        $this->category = $request->query('category');
        $this->filterByTag = $request->query('filterByTag');
        $this->author = $request->query('author') !== null
            ? (int) $request->query('author')
            : null;

        $validated = Validator::make(
            [
                'filterByTag' => $this->filterByTag,
                'author' => $this->author,
                'category' => $this->category,
                'perPage' => $request->query($perPageParam),
            ],
            $this->rules(),
            $this->messages(),
        )->validate();

        $this->category = $validated['category'] ?? null;
        $this->filterByTag = $validated['filterByTag'] ?? null;
        $this->author = isset($validated['author']) ? (int) $validated['author'] : null;
        $this->perPage = $this->sanitizePerPage(
            isset($validated['perPage'])
                ? (int) $validated['perPage']
                : $this->defaultPerPage()
        );
    }

    protected function rules(): array
    {
        return [
            'filterByTag' => ['nullable', 'string', 'max:50'],
            'author' => ['nullable', 'integer', 'exists:users,id'],
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'perPage' => ['nullable', 'integer', Rule::in($this->availablePerPageOptions())],
        ];
    }

    protected function messages(): array
    {
        return [
            'filterByTag.string' => __('validation.posts.filter_tag_string'),
            'filterByTag.max' => __('validation.posts.filter_tag_max'),
            'author.integer' => __('validation.posts.author_integer'),
            'author.exists' => __('validation.posts.author_exists'),
            'category.string' => __('validation.posts.category_string'),
            'category.exists' => __('validation.posts.category_exists'),
            'perPage.in' => __('validation.posts.per_page_options'),
        ];
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingFilterByTag(): void
    {
        $this->resetPage();
    }

    public function updatingAuthor(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->category = null;
        $this->filterByTag = null;
        $this->author = null;
        $this->resetPage();
    }

    public function with(): array
    {
        $categories = Category::orderBy('name')->get();
        $activeCategory = $categories->firstWhere('slug', $this->category);

        $query = Post::query()
            ->with(['author'])
            ->withCount([
                'comments as comments_count' => fn ($query) => $query->approved(),
            ]);

        $title = __('posts.title');
        $filterLabel = null;

        if ($this->filterByTag) {
            $query->whereJsonContains('tags', $this->filterByTag);

            $title = 'Posts with '.__('blog.tag').' '.$this->filterByTag;
            $filterLabel = 'Filtered by '.__('blog.tag').' "'.$this->filterByTag.'"';
        } elseif ($this->author) {
            $author = User::find($this->author);

            if ($author) {
                $query->where('user_id', $author->id);

                $title = 'Posts by '.$author->name;
                $filterLabel = 'Filtered by author '.$author->name;
            } else {
                $this->author = null;
            }
        } elseif ($activeCategory) {
            $query->whereHas('categories', fn (Builder $builder) => $builder->where('categories.id', $activeCategory->id));

            $title = __('posts.filtered_by_category', ['category' => $activeCategory->name]);
            $filterLabel = __('posts.category_filter_badge', ['category' => $activeCategory->name]);
        }

        $posts = $query
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate($this->perPage)
            ->withQueryString();

        $isFiltered = $filterLabel !== null;
        $subtitle = $isFiltered ? $filterLabel : __('posts.subtitle_latest');
        $countLabel = trans_choice('posts.count_label', $posts->total(), ['count' => $posts->total()]);
        $emptyDescription = $isFiltered ? __('posts.reset_filters_hint') : __('posts.empty_default');

        title($title);

        return [
            'title' => $title,
            'posts' => $posts,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'titleText' => $title,
            'subtitle' => $subtitle,
            'countLabel' => $countLabel,
            'emptyDescription' => $emptyDescription,
            'isFiltered' => $isFiltered,
            'filterLabel' => $filterLabel,
        ];
    }
}; ?>

<div class="space-y-8">
    <x-ui.page-header
        :title="$titleText"
        :subtitle="$subtitle"
    >
        <x-slot name="meta">
            <x-ui.badge>{{ $countLabel }}</x-ui.badge>
            @if ($isFiltered && $filterLabel)
                <x-ui.badge variant="info" :uppercase="false">
                    {{ $filterLabel }}
                </x-ui.badge>
            @endif
        </x-slot>
    </x-ui.page-header>

    @if ($categories->isNotEmpty())
        <x-ui.surface tag="section" class="space-y-4 dark:text-slate-100">
            <div class="flex flex-col gap-4 md:flex-row md:items-end">
                <div class="flex-1">
                    <label for="category-filter" class="text-sm font-semibold text-slate-600 dark:text-slate-300">
                        {{ __('posts.category_filter_label') }}
                    </label>
                    <select
                        id="category-filter"
                        wire:model.live="category"
                        class="mt-2 block w-full rounded-2xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    >
                        <option value="">{{ __('posts.category_filter_placeholder') }}</option>
                        @foreach ($categories as $categoryOption)
                            <option value="{{ $categoryOption->slug }}">
                                {{ $categoryOption->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
                @if ($isFiltered)
                    <div class="flex gap-3">
                        <x-ui.button type="button" variant="secondary" wire:click="clearFilters">
                            {{ __('posts.clear_filters') }}
                        </x-ui.button>
                    </div>
                @endif
            </div>
        </x-ui.surface>
    @endif

    <x-ui.section class="pb-16">
        @if ($posts->count())
            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>

            <x-ui.pagination
                :paginator="$posts"
                per-page-mode="livewire"
                per-page-field="perPage"
                :per-page-value="$perPage"
                :per-page-options="$this->perPageOptions"
                :show-per-page="count($this->perPageOptions) > 1"
                summary-key="posts.pagination_summary"
                class="mt-10"
            />
        @else
            <x-ui.empty-state
                :title="__('posts.no_posts_found')"
                :description="$emptyDescription"
            >
                @if ($isFiltered)
                    <x-ui.button type="button" variant="secondary" wire:click="clearFilters">
                        {{ __('posts.clear_filters') }}
                    </x-ui.button>
                @endif
                <x-ui.button href="{{ route('home') }}">
                    {{ __('posts.go_home') }}
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.section>
</div>
