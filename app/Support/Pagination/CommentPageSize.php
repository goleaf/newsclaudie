<?php

declare(strict_types=1);

namespace App\Support\Pagination;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

/**
 * Centralizes comment thread pagination defaults and validation.
 */
final class CommentPageSize
{
    public const QUERY_PARAM = 'comments_per_page';

    /**
     * Default comments-per-page value pulled from configuration.
     */
    public static function default(): int
    {
        return max(1, (int) config('blog.commentsPerPage', PageSize::contextDefault('comments')));
    }

    /**
     * Sanitized, unique page-size options for comment threads.
     *
     * @return array<int>
     */
    public static function options(): array
    {
        $configured = config('interface.pagination.options.comments', []);

        return PageSize::options($configured, self::default());
    }

    /**
     * Resolve the requested comments-per-page from the current request.
     */
    public static function resolveFromRequest(Request $request): int
    {
        return PageSize::resolve(
            $request->integer(self::QUERY_PARAM),
            self::options(),
            self::default(),
        );
    }

    public static function queryParam(): string
    {
        return self::QUERY_PARAM;
    }

    public static function locatePage(Comment $comment, Post $post, int $perPage): int
    {
        if ($comment->post_id !== $post->id) {
            return 1;
        }

        $perPage = max(1, $perPage);

        $preceding = $post->comments()
            ->approved()
            ->when(
                $comment->created_at,
                function ($query) use ($comment) {
                    $query->where(function ($query) use ($comment) {
                        $query->where('created_at', '>', $comment->created_at)
                            ->orWhere(function ($subQuery) use ($comment) {
                                $subQuery
                                    ->where('created_at', $comment->created_at)
                                    ->where('id', '>', $comment->id);
                            });
                    });
                },
                function ($query) use ($comment) {
                    $query->where('id', '>', $comment->id);
                }
            )
            ->count();

        return (int) floor($preceding / $perPage) + 1;
    }
}
