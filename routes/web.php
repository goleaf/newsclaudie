<?php

declare(strict_types=1);

use App\Http\Controllers\CommentController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PostExportController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReadmeController;
use App\Enums\CommentStatus;
use App\Models\Post;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $latestPosts = Post::with(['author'])
        ->withCount([
            'comments as comments_count' => fn ($query) => $query->approved(),
        ])
        ->latest()
        ->take(6)
        ->get();

    return view('welcome', [
        'postCount' => Post::count(),
        'latestPosts' => $latestPosts,
    ]);
})->name('home');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/news', [NewsController::class, 'index'])
    ->name('news.index')
    ->middleware(['throttle:news', 'cache.headers:public;max_age=300']);

Volt::route('posts', 'posts.index')->name('posts.index');
Route::resource('posts', PostController::class)->except(['index']);
Route::post('/posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');
Route::post('/posts/{post}/unpublish', [PostController::class, 'unpublish'])->name('posts.unpublish');

Volt::route('categories', 'categories.index')->name('categories.index');
Route::resource('categories', App\Http\Controllers\CategoryController::class)->except(['index', 'show']);
Volt::route('categories/{category}', 'categories.show')->name('categories.show');

// Comment routes with rate limiting for security
Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
    ->middleware('throttle:comments')
    ->name('posts.comments.store');

Route::resource('comments', CommentController::class)->only([
    'edit',
    'update',
    'destroy',
])->middleware('throttle:60,1'); // 60 requests per minute for edits/deletes

Route::middleware(['auth', 'can:access-admin'])->group(function () {
    Volt::route('admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
    Volt::route('admin/posts', 'admin.posts.index')->name('admin.posts.index');
    Volt::route('admin/posts/create', 'admin.posts.form')->name('admin.posts.create');
    Volt::route('admin/posts/{post}/edit', 'admin.posts.form')->name('admin.posts.edit');
    Volt::route('admin/categories', 'admin.categories.index')->name('admin.categories.index');
    Volt::route('admin/comments', 'admin.comments.index')->name('admin.comments.index');
    Volt::route('admin/users', 'admin.users.index')->name('admin.users.index');
    Route::post('admin/posts/export', [PostExportController::class, 'store'])->name('admin.posts.export');
    Route::get('admin/posts/export/{export}', [PostExportController::class, 'download'])
        ->middleware('signed')
        ->name('admin.posts.export.download');
});

if (config('blog.readme')) {
    Route::get('/readme', ReadmeController::class)->name('readme');
}

require __DIR__.'/auth.php';
