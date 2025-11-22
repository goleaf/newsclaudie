<?php

declare(strict_types=1);

use App\Http\Controllers\CommentController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReadmeController;
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
        ->withCount('comments')
        ->latest()
        ->take(6)
        ->get();

    return view('welcome', [
        'postCount' => Post::count(),
        'latestPosts' => $latestPosts,
    ]);
})->name('home');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::resource('posts', PostController::class);
Route::post('/posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');
Route::post('/posts/{post}/unpublish', [PostController::class, 'unpublish'])->name('posts.unpublish');

Route::resource('categories', App\Http\Controllers\CategoryController::class);

Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('posts.comments.store');
Route::resource('comments', CommentController::class)->only([
    'edit',
    'update',
    'destroy',
]);

Route::middleware(['auth', 'can:access-admin'])->group(function () {
    Volt::route('admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
    Volt::route('admin/posts', 'admin.posts.index')->name('admin.posts.index');
    Volt::route('admin/categories', 'admin.categories.index')->name('admin.categories.index');
    Volt::route('admin/comments', 'admin.comments.index')->name('admin.comments.index');
    Volt::route('admin/users', 'admin.users.index')->name('admin.users.index');
});

if (config('blog.readme')) {
    Route::get('/readme', ReadmeController::class)->name('readme');
}

require __DIR__.'/auth.php';
