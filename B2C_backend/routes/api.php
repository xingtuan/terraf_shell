<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\CommentModerationController;
use App\Http\Controllers\Api\Admin\PostModerationController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\TagController as AdminTagController;
use App\Http\Controllers\Api\Admin\UserModerationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CommentLikeController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostLikeController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware('throttle:password-reset')->group(function (): void {
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])
        ->name('password.reset');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:verification'])
        ->name('verification.verify');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/profile', [ProfileController::class, 'update']);
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
            ->middleware('throttle:verification');
    });
});

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->whereNumber('post');
Route::get('/posts/{identifier}', [PostController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/tags', [TagController::class, 'index']);
Route::post('/inquiries', [InquiryController::class, 'store']);
Route::get('/users/{user}/posts', [UserController::class, 'posts'])->whereNumber('user');
Route::get('/users/{user}/comments', [UserController::class, 'comments'])->whereNumber('user');
Route::get('/users/{user}/followers', [UserController::class, 'followers'])->whereNumber('user');
Route::get('/users/{user}/following', [UserController::class, 'following'])->whereNumber('user');
Route::get('/users/{user}', [UserController::class, 'show'])->whereNumber('user');
Route::get('/search/posts', [SearchController::class, 'posts']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->whereNumber('notification');
});

Route::middleware(['auth:sanctum', 'not_banned'])->group(function (): void {
    Route::post('/posts', [PostController::class, 'store']);
    Route::patch('/posts/{post}', [PostController::class, 'update'])->whereNumber('post');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->whereNumber('post');

    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->whereNumber('post');
    Route::post('/comments/{comment}/reply', [CommentController::class, 'reply'])->whereNumber('comment');
    Route::patch('/comments/{comment}', [CommentController::class, 'update'])->whereNumber('comment');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->whereNumber('comment');

    Route::post('/posts/{post}/like', [PostLikeController::class, 'store'])->whereNumber('post');
    Route::delete('/posts/{post}/like', [PostLikeController::class, 'destroy'])->whereNumber('post');
    Route::post('/comments/{comment}/like', [CommentLikeController::class, 'store'])->whereNumber('comment');
    Route::delete('/comments/{comment}/like', [CommentLikeController::class, 'destroy'])->whereNumber('comment');

    Route::post('/posts/{post}/favorite', [FavoriteController::class, 'store'])->whereNumber('post');
    Route::delete('/posts/{post}/favorite', [FavoriteController::class, 'destroy'])->whereNumber('post');

    Route::post('/users/{user}/follow', [FollowController::class, 'store'])->whereNumber('user');
    Route::delete('/users/{user}/follow', [FollowController::class, 'destroy'])->whereNumber('user');

    Route::post('/reports', [ReportController::class, 'store']);
});

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin,moderator'])
    ->group(function (): void {
        Route::get('/reports', [AdminReportController::class, 'index']);
        Route::patch('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->whereNumber('report');
        Route::patch('/posts/{post}/status', [PostModerationController::class, 'updateStatus'])->whereNumber('post');
        Route::patch('/comments/{comment}/status', [CommentModerationController::class, 'updateStatus'])->whereNumber('comment');
        Route::patch('/users/{user}/role', [UserModerationController::class, 'updateRole'])->whereNumber('user');
        Route::patch('/users/{user}/account-status', [UserModerationController::class, 'updateAccountStatus'])->whereNumber('user');
        Route::patch('/users/{user}/ban', [UserModerationController::class, 'ban'])->whereNumber('user');

        Route::get('/categories', [AdminCategoryController::class, 'index']);
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::get('/categories/{category}', [AdminCategoryController::class, 'show'])->whereNumber('category');
        Route::patch('/categories/{category}', [AdminCategoryController::class, 'update'])->whereNumber('category');
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->whereNumber('category');

        Route::get('/tags', [AdminTagController::class, 'index']);
        Route::post('/tags', [AdminTagController::class, 'store']);
        Route::get('/tags/{tag}', [AdminTagController::class, 'show'])->whereNumber('tag');
        Route::patch('/tags/{tag}', [AdminTagController::class, 'update'])->whereNumber('tag');
        Route::delete('/tags/{tag}', [AdminTagController::class, 'destroy'])->whereNumber('tag');
    });
