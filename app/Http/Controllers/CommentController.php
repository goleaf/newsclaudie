<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CommentStatus;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Support\Pagination\CommentPageSize;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated()['content'],
            'status' => CommentStatus::Pending,
        ]);

        $perPage = CommentPageSize::resolveFromRequest($request);

        return $this->redirectToComment($comment, $post, __('comments.created'), $perPage);
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

        $perPage = CommentPageSize::resolveFromRequest($request);

        return $this->redirectToComment($comment, $comment->post, __('comments.updated'), $perPage);
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('success', __('comments.deleted'));
    }

    private function redirectToComment(Comment $comment, Post $post, string $message, ?int $perPage = null): RedirectResponse
    {
        $defaultPerPage = CommentPageSize::default();
        $perPage = $perPage ?? $defaultPerPage;
        $page = CommentPageSize::locatePage($comment, $post, $perPage);

        $routeParameters = [
            'post' => $post,
        ];

        if ($perPage !== $defaultPerPage) {
            $routeParameters[CommentPageSize::queryParam()] = $perPage;
        }

        if ($page > 1) {
            $routeParameters['page'] = $page;
        }

        if (! $comment->isApproved()) {
            return redirect()
                ->route('posts.show', $routeParameters)
                ->with('success', $message);
        }

        return redirect()
            ->route('posts.show', $routeParameters)
            ->withFragment('comment-'.$comment->id)
            ->with('success', $message);
    }
}
