@props([
    'comment',
])

@php
    $author = $comment->user?->name ?? __('post.comments.unknown_user');
    $createdAt = $comment->created_at?->diffForHumans();
    $wasEdited = $comment->updated_at && $comment->created_at && $comment->updated_at->ne($comment->created_at);
@endphp

<li {{ $attributes->class('rounded-2xl border border-slate-200/80 bg-white/70 p-4 dark:border-slate-700 dark:bg-slate-900/70') }}>
    <div class="flex flex-col gap-2 text-xs uppercase tracking-wide text-slate-400 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2 text-slate-500 dark:text-slate-300">
            <span class="text-slate-600 dark:text-slate-100">&commat;{{ $author }}</span>
            @if ($createdAt)
                <span>{{ $createdAt }}</span>
            @endif
            @if ($wasEdited)
                <span class="text-[10px] text-slate-400">{{ __('post.comments.edited_label') }}</span>
            @endif
        </div>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2 text-[11px]">
                {{ $actions }}
            </div>
        @endisset
    </div>

    <p class="mt-3 text-sm leading-relaxed text-slate-700 dark:text-slate-200">
        {!! nl2br(e($comment->content)) !!}
    </p>
</li>



