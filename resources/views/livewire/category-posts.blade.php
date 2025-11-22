<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Models\Category;
use App\Support\Pagination\PageSize;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
    use ManagesPerPage;

    public Category $category;

    protected string $pageName = 'categoryPage';

    protected array $queryString = [
        'page' => ['except' => 1],
    ];

    protected function perPageContext(): string
    {
        return 'category_posts';
    }

    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->queryString['perPage'] = ['as' => PageSize::queryParam(), 'except' => null];
        $this->perPage = $this->sanitizePerPage(
            $this->perPage ?: $this->defaultPerPage()
        );
    }

    public function with(): array
    {
        $posts = $this->category->posts()
            ->with(['author'])
            ->withCount([
                'comments as comments_count' => fn ($query) => $query->approved(),
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate($this->perPage, ['*'], $this->pageName)
            ->withQueryString();

        return [
            'posts' => $posts,
        ];
    }
}; ?>

<div class="space-y-8">
    @if ($posts->count())
        <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
            @foreach ($posts as $post)
                <x-post-card :post="$post" />
            @endforeach
        </div>

        <x-ui.pagination
            class="mt-8"
            :paginator="$posts"
            per-page-mode="livewire"
            per-page-field="perPage"
            :per-page-value="$perPage"
            :per-page-options="$this->perPageOptions"
            :show-per-page="count($this->perPageOptions) > 1"
            summary-key="categories.show.pagination_summary"
            align="left"
            variant="plain"
        />
    @else
        <x-ui.empty-state
            :title="__('categories.show.empty_title')"
            :description="__('categories.show.empty_subtitle')"
        >
            <x-ui.button href="{{ route('categories.index') }}" variant="secondary">
                {{ __('categories.show.view_all') }}
            </x-ui.button>
        </x-ui.empty-state>
    @endif
</div>
