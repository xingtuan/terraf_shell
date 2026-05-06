<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Api\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Api\Admin\B2BLeadController as AdminB2BLeadController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\CommentModerationController;
use App\Http\Controllers\Api\Admin\FundingCampaignController as AdminFundingCampaignController;
use App\Http\Controllers\Api\Admin\GovernanceController;
use App\Http\Controllers\Api\Admin\HomeSectionController as AdminHomeSectionController;
use App\Http\Controllers\Api\Admin\MaterialApplicationController as AdminMaterialApplicationController;
use App\Http\Controllers\Api\Admin\MaterialController as AdminMaterialController;
use App\Http\Controllers\Api\Admin\MaterialSpecController as AdminMaterialSpecController;
use App\Http\Controllers\Api\Admin\MaterialStorySectionController as AdminMaterialStorySectionController;
use App\Http\Controllers\Api\Admin\PostModerationController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\SystemAnnouncementController;
use App\Http\Controllers\Api\Admin\TagController as AdminTagController;
use App\Http\Controllers\Api\Admin\UserModerationController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessContactController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CommentLikeController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\HomepageController;
use App\Http\Controllers\Api\HomeSectionController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PartnershipInquiryController;
use App\Http\Controllers\Api\PostAttachmentDownloadController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostLikeController;
use App\Http\Controllers\Api\ProductCategoryController as PublicProductCategoryController;
use App\Http\Controllers\Api\ProductController as PublicProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SampleRequestController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\StoreShippingController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\UploadController;
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
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
            ->middleware('throttle:verification');
    });
});

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->whereNumber('post');
Route::get('/posts/{identifier}/attachments/{media}/download', PostAttachmentDownloadController::class)
    ->whereNumber('media')
    ->name('posts.attachments.download');
Route::get('/posts/{identifier}', [PostController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/tags', [TagController::class, 'index']);
Route::get('/homepage', [HomepageController::class, 'show']);
Route::get('/home-sections', [HomeSectionController::class, 'index']);
Route::get('/materials', [MaterialController::class, 'index']);
Route::get('/materials/{identifier}', [MaterialController::class, 'show']);
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{identifier}', [ArticleController::class, 'show']);
Route::get('/product-categories', [PublicProductCategoryController::class, 'index']);
Route::get('/products', [PublicProductController::class, 'index']);
Route::get('/products/featured', [PublicProductController::class, 'featured']);
Route::get('/products/{slug}', [PublicProductController::class, 'show']);
Route::get('/store/address-search', [StoreShippingController::class, 'addressSearch']);
Route::get('/store/address-details', [StoreShippingController::class, 'addressDetails']);
Route::post('/store/shipping-options', [StoreShippingController::class, 'shippingOptions']);
Route::prefix('cart')->group(function (): void {
    Route::get('/', [CartController::class, 'show']);
    Route::post('/items', [CartController::class, 'addItem']);
    Route::patch('/items/{productId}', [CartController::class, 'updateItem']);
    Route::delete('/items/{productId}', [CartController::class, 'removeItem']);
    Route::delete('/', [CartController::class, 'clear']);
    Route::post('/merge', [CartController::class, 'merge'])
        ->middleware('auth:sanctum');
});
Route::middleware('throttle:leads')->group(function (): void {
    Route::post('/inquiries', [InquiryController::class, 'store']);
    Route::post('/business-contacts', [BusinessContactController::class, 'store']);
    Route::post('/partnership-inquiries', [PartnershipInquiryController::class, 'store']);
    Route::post('/sample-requests', [SampleRequestController::class, 'store']);
    Route::post('/university-collaborations', [PartnershipInquiryController::class, 'storeUniversity']);
    Route::post('/product-development-collaborations', [PartnershipInquiryController::class, 'storeProductDevelopment']);
});
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/guest/{orderNumber}', [OrderController::class, 'showGuest']);
Route::get('/users/{user}/posts', [UserController::class, 'posts']);
Route::get('/users/{user}/favorites', [UserController::class, 'favorites']);
Route::get('/users/{user}/comments', [UserController::class, 'comments']);
Route::get('/users/{user}/followers', [UserController::class, 'followers']);
Route::get('/users/{user}/following', [UserController::class, 'following']);
Route::get('/users/{user}', [UserController::class, 'show']);
Route::get('/search', [SearchController::class, 'index']);
Route::get('/search/posts', [SearchController::class, 'posts']);

if ((bool) config('community.uploads.allow_guest_upload', false)) {
    Route::post('/media/upload/guest', [UploadController::class, 'upload']);
}

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/media/upload', [UploadController::class, 'upload']);
    Route::delete('/media', [UploadController::class, 'destroy']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->whereNumber('notification');
    Route::apiResource('orders', OrderController::class)
        ->only(['index', 'show', 'destroy'])
        ->parameters(['orders' => 'orderNumber']);
    Route::apiResource('addresses', AddressController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('addresses/{id}/default', [AddressController::class, 'setDefault']);
});

Route::middleware(['auth:sanctum', 'not_banned'])->group(function (): void {
    Route::post('/posts', [PostController::class, 'store']);
    Route::patch('/posts/{post}', [PostController::class, 'update'])->whereNumber('post');
    Route::put('/posts/{post}', [PostController::class, 'update'])->whereNumber('post');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->whereNumber('post');

    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->whereNumber('post');
    Route::post('/comments/{comment}/reply', [CommentController::class, 'reply'])->whereNumber('comment');
    Route::patch('/comments/{comment}', [CommentController::class, 'update'])->whereNumber('comment');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])->whereNumber('comment');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->whereNumber('comment');

    Route::post('/posts/{post}/like', [PostLikeController::class, 'store'])->whereNumber('post');
    Route::delete('/posts/{post}/like', [PostLikeController::class, 'destroy'])->whereNumber('post');
    Route::post('/comments/{comment}/like', [CommentLikeController::class, 'store'])->whereNumber('comment');
    Route::delete('/comments/{comment}/like', [CommentLikeController::class, 'destroy'])->whereNumber('comment');

    Route::post('/posts/{post}/favorite', [FavoriteController::class, 'store'])->whereNumber('post');
    Route::delete('/posts/{post}/favorite', [FavoriteController::class, 'destroy'])->whereNumber('post');

    Route::post('/users/{user}/follow', [FollowController::class, 'store']);
    Route::delete('/users/{user}/follow', [FollowController::class, 'destroy']);

    Route::post('/reports', [ReportController::class, 'store']);
});

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin,moderator'])
    ->group(function (): void {
        Route::get('/reports', [AdminReportController::class, 'index']);
        Route::patch('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->whereNumber('report');
        Route::patch('/posts/{post}/status', [PostModerationController::class, 'updateStatus'])->whereNumber('post');
        Route::get('/posts/ranking-formula', [PostModerationController::class, 'rankingFormula']);
        Route::patch('/comments/{comment}/status', [CommentModerationController::class, 'updateStatus'])->whereNumber('comment');
        Route::get('/users/{user}/moderation-history', [GovernanceController::class, 'userModerationHistory'])->whereNumber('user');
        Route::get('/users/{user}/admin-actions', [GovernanceController::class, 'userAdminActions'])->whereNumber('user');
        Route::get('/users/{user}/violations', [GovernanceController::class, 'userViolations'])->whereNumber('user');
        Route::post('/users/{user}/violations', [GovernanceController::class, 'storeUserViolation'])->whereNumber('user');
        Route::patch('/users/{user}/violations/{violation}', [GovernanceController::class, 'updateUserViolation'])->whereNumber('user')->whereNumber('violation');
        Route::get('/posts/{post}/review-history', [GovernanceController::class, 'postReviewHistory'])->whereNumber('post');
        Route::get('/comments/{comment}/review-history', [GovernanceController::class, 'commentReviewHistory'])->whereNumber('comment');
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

        Route::middleware('role:admin')->group(function (): void {
            Route::get('/analytics/overview', [AdminAnalyticsController::class, 'overview']);
            Route::patch('/posts/{post}/feature', [PostModerationController::class, 'updateFeaturedStatus'])->whereNumber('post');
            Route::get('/posts/{post}/funding-campaign', [AdminFundingCampaignController::class, 'show'])->whereNumber('post');
            Route::patch('/posts/{post}/funding-campaign', [AdminFundingCampaignController::class, 'update'])->whereNumber('post');
            Route::delete('/posts/{post}/funding-campaign', [AdminFundingCampaignController::class, 'destroy'])->whereNumber('post');
            Route::post('/notifications/announcements', [SystemAnnouncementController::class, 'store']);
            Route::get('/b2b-leads/export', [AdminB2BLeadController::class, 'export']);
            Route::get('/b2b-leads', [AdminB2BLeadController::class, 'index']);
            Route::get('/b2b-leads/{b2bLead}', [AdminB2BLeadController::class, 'show'])->whereNumber('b2bLead');
            Route::patch('/b2b-leads/{b2bLead}', [AdminB2BLeadController::class, 'update'])->whereNumber('b2bLead');

            Route::get('/materials', [AdminMaterialController::class, 'index']);
            Route::post('/materials', [AdminMaterialController::class, 'store']);
            Route::get('/materials/{material}', [AdminMaterialController::class, 'show'])->whereNumber('material');
            Route::patch('/materials/{material}', [AdminMaterialController::class, 'update'])->whereNumber('material');
            Route::delete('/materials/{material}', [AdminMaterialController::class, 'destroy'])->whereNumber('material');

            Route::get('/material-specs', [AdminMaterialSpecController::class, 'index']);
            Route::post('/material-specs', [AdminMaterialSpecController::class, 'store']);
            Route::get('/material-specs/{materialSpec}', [AdminMaterialSpecController::class, 'show'])->whereNumber('materialSpec');
            Route::patch('/material-specs/{materialSpec}', [AdminMaterialSpecController::class, 'update'])->whereNumber('materialSpec');
            Route::delete('/material-specs/{materialSpec}', [AdminMaterialSpecController::class, 'destroy'])->whereNumber('materialSpec');

            Route::get('/material-story-sections', [AdminMaterialStorySectionController::class, 'index']);
            Route::post('/material-story-sections', [AdminMaterialStorySectionController::class, 'store']);
            Route::get('/material-story-sections/{materialStorySection}', [AdminMaterialStorySectionController::class, 'show'])->whereNumber('materialStorySection');
            Route::patch('/material-story-sections/{materialStorySection}', [AdminMaterialStorySectionController::class, 'update'])->whereNumber('materialStorySection');
            Route::delete('/material-story-sections/{materialStorySection}', [AdminMaterialStorySectionController::class, 'destroy'])->whereNumber('materialStorySection');

            Route::get('/material-applications', [AdminMaterialApplicationController::class, 'index']);
            Route::post('/material-applications', [AdminMaterialApplicationController::class, 'store']);
            Route::get('/material-applications/{materialApplication}', [AdminMaterialApplicationController::class, 'show'])->whereNumber('materialApplication');
            Route::patch('/material-applications/{materialApplication}', [AdminMaterialApplicationController::class, 'update'])->whereNumber('materialApplication');
            Route::delete('/material-applications/{materialApplication}', [AdminMaterialApplicationController::class, 'destroy'])->whereNumber('materialApplication');

            Route::get('/articles', [AdminArticleController::class, 'index']);
            Route::post('/articles', [AdminArticleController::class, 'store']);
            Route::get('/articles/{article}', [AdminArticleController::class, 'show'])->whereNumber('article');
            Route::patch('/articles/{article}', [AdminArticleController::class, 'update'])->whereNumber('article');
            Route::delete('/articles/{article}', [AdminArticleController::class, 'destroy'])->whereNumber('article');

            Route::get('/home-sections', [AdminHomeSectionController::class, 'index']);
            Route::post('/home-sections', [AdminHomeSectionController::class, 'store']);
            Route::get('/home-sections/{homeSection}', [AdminHomeSectionController::class, 'show'])->whereNumber('homeSection');
            Route::patch('/home-sections/{homeSection}', [AdminHomeSectionController::class, 'update'])->whereNumber('homeSection');
            Route::delete('/home-sections/{homeSection}', [AdminHomeSectionController::class, 'destroy'])->whereNumber('homeSection');
        });
    });
