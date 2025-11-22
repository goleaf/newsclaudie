@props([
    'comments',
    'emptyMessage' => null,
    'showSummary' => true,
    'perPageOptions' => [],
    'perPageField' => 'comments_per_page',
    'perPageValue' => null,
    'perPageAnchor' => null,
    'showActions' => true,
])

@php
    $isPaginator = is_object($comments) && method_exists($comments, 'hasPages');
    $hasPagination = $isPaginator && $comments->hasPages();
    $hasItems = $isPaginator ? $comments->count() > 0 : (is_countable($comments) ? count($comments) > 0 : false);
    $fallbackMessage = $emptyMessage ?? __('post.comments.empty');

    $sanitizedOptions = collect($perPageOptions)
        ->map(fn ($value) => (int) $value)
        ->filter(fn ($value) => $value > 0)
        ->unique()
        ->values();

    $showPerPageControl = $hasPagination && $sanitizedOptions->isNotEmpty();
    $perPageAction = $perPageAnchor
        ? sprintf('%s#%s', request()->url(), ltrim($perPageAnchor, '#'))
        : null;
@endphp

@if ($hasItems)
    <ul class="space-y-4">
        @foreach ($comments as $comment)
            <x-ui.comment :comment="$comment">
                @if ($showActions)
                    <x-slot name="actions">
                        @can('update', $comment)
                            <x-link href="{{ route('comments.edit', ['comment' => $comment]) }}">{{ __('post.edit') }}</x-link>
                        @endcan
                        @can('delete', $comment)
                            <form
                                action="{{ route('comments.destroy', ['comment' => $comment]) }}"
                                method="POST"
                                onsubmit="return confirm('{{ __('post.comment_delete_confirm') }}');"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-500 hover:text-rose-400">{{ __('post.delete') }}</button>
                            </form>
                        @endcan
                    </x-slot>
                @endif
            </x-ui.comment>
        @endforeach
    </ul>
@else
    <p class="text-sm text-slate-500 dark:text-slate-400">
        {{ $fallbackMessage }}
    </p>
@endif

@if ($hasPagination)
    <x-ui.pagination
        class="border-t border-slate-200/70 pt-4 dark:border-slate-800/70"
        :paginator="$comments"
        summary-key="post.comments.pagination_summary"
        :show-summary="$showSummary"
        align="left"
        variant="plain"
        per-page-mode="http"
        :per-page-options="$sanitizedOptions->all()"
        :per-page-field="$perPageField"
        :per-page-value="$perPageValue"
        :per-page-form-action="$perPageAction"
        :show-per-page="$showPerPageControl"
    />
@endif
