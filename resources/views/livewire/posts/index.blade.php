<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
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

        $exportNotifications = collect();

        if (auth()->check()) {
            $exportNotifications = auth()->user()
                ->unreadNotifications()
                ->where('type', ExportReadyNotification::class)
                ->get();

            if ($exportNotifications->isNotEmpty()) {
                auth()->user()->unreadNotifications()
                    ->whereIn('id', $exportNotifications->pluck('id'))
                    ->update(['read_at' => now()]);
            }
        }

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
            'exportNotifications' => $exportNotifications,
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

    @if (session('export_status'))
        <x-ui.alert :variant="session('export_url') ? 'success' : 'info'" class="dark:text-slate-100">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <p>{{ session('export_status') }}</p>
                    @if (session('export_expires'))
                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            {{ __('admin.exports.expires', ['time' => session('export_expires')]) }}
                        </p>
                    @endif
                </div>
                @if (session('export_url'))
                    <x-ui.button href="{{ session('export_url') }}" target="_blank" rel="noopener">
                        {{ __('admin.exports.download') }}
                    </x-ui.button>
                @endif
            </div>
        </x-ui.alert>
    @endif

    @if ($exportNotifications->isNotEmpty())
        @foreach ($exportNotifications as $notification)
            <x-ui.alert variant="success" class="dark:text-slate-100">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1">
                        <p class="font-semibold">
                            {{ $notification->data['message'] ?? __('admin.exports.ready_title') }}
                        </p>
                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            {{ __('admin.exports.notification_body', [
                                'format' => strtoupper($notification->data['format'] ?? 'CSV'),
                                'count' => $notification->data['rows'] ?? 0,
                            ]) }}
                        </p>
                        @if (! empty($notification->data['expires_at']))
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ __('admin.exports.expires', ['time' => \Illuminate\Support\Carbon::parse($notification->data['expires_at'])->toDayDateTimeString()]) }}
                            </p>
                        @endif
                    </div>
                    @if (! empty($notification->data['url']))
                        <x-ui.button href="{{ $notification->data['url'] }}">
                            {{ __('admin.exports.download') }}
                        </x-ui.button>
                    @endif
                </div>
            </x-ui.alert>
        @endforeach
    @endif

    @can('access-admin')
        <x-ui.surface tag="section" class="space-y-3 dark:text-slate-100">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-base font-semibold text-slate-900 dark:text-white">
                        {{ __('admin.exports.title') }}
                    </p>
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        {{ __('admin.exports.hint') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.posts.export') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    @csrf
                    @if ($category)
                        <input type="hidden" name="category" value="{{ $category }}">
                    @endif
                    @if ($filterByTag)
                        <input type="hidden" name="filterByTag" value="{{ $filterByTag }}">
                    @endif
                    @if ($author)
                        <input type="hidden" name="author" value="{{ $author }}">
                    @endif
                    <label for="export-format" class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {{ __('admin.exports.format_label') }}
                    </label>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <select
                            id="export-format"
                            name="format"
                            class="w-full rounded-2xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 sm:w-40"
                        >
                            <option value="csv" @selected(old('format', 'csv') === 'csv')>{{ __('admin.exports.format_csv') }}</option>
                            <option value="json" @selected(old('format', 'csv') === 'json')>{{ __('admin.exports.format_json') }}</option>
                        </select>
                        <x-ui.button type="submit">
                            {{ __('admin.exports.export_button') }}
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.surface>
    @endcan

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
