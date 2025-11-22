<?php

use App\Enums\CommentStatus;
use App\Livewire\Concerns\ManagesPerPage;
use App\Models\Comment;
use App\Models\Post;
use App\Support\Pagination\CommentPageSize;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use AuthorizesRequests;
    use ManagesPerPage;
    use WithPagination;

    public Post $post;
    public string $content = '';

    protected string $pageName = 'commentsPage';
    protected array $queryString = [
        'page' => ['except' => 1],
    ];

    protected function perPageContext(): string
    {
        return 'comments';
    }

    public function mount(Post $post): void
    {
        $this->post = $post;
        $this->queryString['perPage'] = [
            'as' => CommentPageSize::queryParam(),
            'except' => null,
        ];

        $requestedPerPage = request()->integer(CommentPageSize::queryParam()) ?: null;

        $this->perPage = $this->sanitizePerPage(
            $requestedPerPage ?: CommentPageSize::default()
        );
    }

    protected function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:1024'],
        ];
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function saveComment(): void
    {
        $this->authorize('create', Comment::class);

        $validated = $this->validate();

        $this->post->comments()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'status' => CommentStatus::Pending,
        ]);

        $this->reset('content');
        $this->resetPage();

        session()->flash('success', __('comments.created'));
    }

    public function deleteComment(int $commentId): void
    {
        $comment = $this->post->comments()->findOrFail($commentId);

        $this->authorize('delete', $comment);

        $comment->delete();

        session()->flash('success', __('comments.deleted'));

        $this->resetPage();
    }

    public function with(): array
    {
        $comments = $this->post->comments()
            ->with('user')
            ->approved()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($this->perPage, ['*'], $this->pageName)
            ->withQueryString();

        return [
            'comments' => $comments,
            'commentCount' => $comments->total(),
            'commentPerPageOptions' => CommentPageSize::options(),
        ];
    }
}; ?>

<x-ui.card id="comments" class="space-y-6">
    <header class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white">
            {{ __('post.comments.title') }}
        </h2>
        @if ($commentCount)
            <span class="text-sm text-slate-500">
                {{ trans_choice('post.comments.count', $commentCount, ['count' => $commentCount]) }}
            </span>
        @endif
    </header>

    @if (session('success'))
        <x-ui.alert variant="success" size="md">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if ($comments->count())
        <ul class="space-y-4">
            @foreach ($comments as $comment)
                <x-ui.comment :comment="$comment" id="comment-{{ $comment->id }}">
                    <x-slot name="actions">
                        @can('update', $comment)
                            <x-link href="{{ route('comments.edit', ['comment' => $comment]) }}">{{ __('post.edit') }}</x-link>
                        @endcan
                        @can('delete', $comment)
                            <button
                                type="button"
                                class="text-rose-500 transition hover:text-rose-400"
                                wire:click="deleteComment({{ $comment->id }})"
                                wire:confirm="{{ __('post.comment_delete_confirm') }}"
                            >
                                {{ __('post.delete') }}
                            </button>
                        @endcan
                    </x-slot>
                </x-ui.comment>
            @endforeach
        </ul>

        <x-ui.pagination
            class="border-t border-slate-200/70 pt-4 dark:border-slate-800/70"
            :paginator="$comments"
            summary-key="post.comments.pagination_summary"
            :show-summary="false"
            align="left"
            variant="plain"
            per-page-mode="livewire"
            per-page-field="perPage"
            :per-page-options="$commentPerPageOptions"
            :per-page-value="$perPage"
            :show-per-page="count($commentPerPageOptions) > 1"
            aria-label="{{ __('post.comments.title') }}"
        />
    @else
        <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ __('post.comments.empty') }}
        </p>
    @endif

    <div class="pt-2">
        @php
            $commentGate = Gate::inspect('create', Comment::class);
        @endphp

        @if ($commentGate->allowed())
            <form wire:submit.prevent="saveComment" class="space-y-4">
                <div class="space-y-2">
                    <x-label for="comment_content" :value="__('post.comments.label')" />
                    <x-textarea
                        id="comment_content"
                        name="content"
                        rows="4"
                        placeholder="{{ __('post.comments.placeholder') }}"
                        wire:model.defer="content"
                    ></x-textarea>
                    @error('content')
                        <p class="text-sm text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end">
                    <x-ui.button type="submit" wire:loading.attr="disabled">
                        {{ __('post.comments.submit') }}
                    </x-ui.button>
                </div>
            </form>
        @elseif ($commentGate->message() === 'Your email must be verified to comment.')
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('post.verify_email_prompt') }}
                <x-link :href="route('verification.notice')">{{ __('post.resend_email') }}</x-link>
            </p>
        @endif

        @guest
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <x-link :href="route('login')">{{ __('post.login') }}</x-link>
                @if (Route::has('register'))
                    {{ __('post.or') }}
                    <x-link :href="route('register')">{{ __('post.sign_up') }}</x-link>
                @endif
                {{ __('post.comments.login_prompt') }}
            </p>
        @endguest
    </div>
</x-ui.card>
