<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated()['content'],
        ]);

        return $this->redirectToComment($comment, $post, __('comments.created'));
    }

    public function edit(Comment $comment): View
    {
        $this->authorize('update', $comment);

        return view('comments.edit', [
            'comment' => $comment,
        ]);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): RedirectResponse
    {
        $comment->update($request->validated());

        return $this->redirectToComment($comment, $comment->post, __('comments.updated'));
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('success', __('comments.deleted'));
    }

    private function redirectToComment(Comment $comment, Post $post, string $message): RedirectResponse
    {
        $page = $this->resolveCommentPage($comment, $post);

        $routeParameters = ['post' => $post];

        if ($page > 1) {
            $routeParameters['page'] = $page;
        }

        return redirect()
            ->route('posts.show', $routeParameters)
            ->withFragment('comment-'.$comment->id)
            ->with('success', $message);
    }

    private function resolveCommentPage(Comment $comment, Post $post): int
    {
        $perPage = max(1, (int) config('blog.commentsPerPage', 10));

        if (! $comment->created_at) {
            $preceding = $post->comments()
                ->where('id', '>', $comment->id)
                ->count();
        } else {
            $preceding = $post->comments()
                ->where(function ($query) use ($comment) {
                    $query->where('created_at', '>', $comment->created_at)
                        ->orWhere(function ($subQuery) use ($comment) {
                            $subQuery
                                ->where('created_at', $comment->created_at)
                                ->where('id', '>', $comment->id);
                        });
                })
                ->count();
        }

        return (int) floor($preceding / $perPage) + 1;
    }
}
