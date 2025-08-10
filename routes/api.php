<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\Admin\AdminServiceController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication Routes
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    
    // Google OAuth
    Route::get('/google', [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
    
    // Authenticated auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
});

// Public API Routes (no authentication required)
Route::prefix('v1')->group(function () {
    
    // Services
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{service:slug}', [ServiceController::class, 'show']);
    Route::get('/services/{service:slug}/variants', [ServiceController::class, 'variants']);
    
    // Cart (Guest & Authenticated)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{item}', [CartController::class, 'update']);
    Route::delete('/cart/{item}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);
    Route::post('/cart/calculate', [CartController::class, 'calculate']);
    
    // Checkout
    Route::post('/checkout', [OrderController::class, 'checkout']);
    
    // Payments
    Route::post('/payments/stripe/intent', [PaymentController::class, 'createStripeIntent']);
    Route::post('/payments/paypal/order', [PaymentController::class, 'createPayPalOrder']);
    Route::post('/payments/razorpay/order', [PaymentController::class, 'createRazorpayOrder']);
    
    // Webhooks
    Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook']);
    Route::post('/webhooks/paypal', [PaymentController::class, 'paypalWebhook']);
    Route::post('/webhooks/razorpay', [PaymentController::class, 'razorpayWebhook']);
    
    // Public Content
    Route::get('/faqs', [FaqController::class, 'index']);
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{news:slug}', [NewsController::class, 'show']);
    
    // Price Calculator
    Route::post('/calculate-price', [ServiceController::class, 'calculatePrice']);
});

// Authenticated API Routes
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // User profile and account management
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/profile/avatar', [UserController::class, 'uploadAvatar']);
    Route::delete('/profile/avatar', [UserController::class, 'deleteAvatar']);

    // File management
    Route::prefix('files')->name('files.')->group(function () {
        Route::get('/', [FileController::class, 'index'])->name('index');
        Route::post('/', [FileController::class, 'upload'])->name('upload');
        Route::post('/bulk', [FileController::class, 'bulkUpload'])->name('bulk-upload');
        Route::get('/storage-usage', [FileController::class, 'storageUsage'])->name('storage-usage');
        Route::get('/{id}', [FileController::class, 'show'])->name('show');
        Route::get('/{id}/download', [FileController::class, 'download'])->name('download');
        Route::delete('/{id}', [FileController::class, 'destroy'])->name('destroy');
    });
    
    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/message', [OrderController::class, 'addMessage']);
    Route::get('/orders/{order}/files', [OrderController::class, 'files']);
    
    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{wishlist}', [WishlistController::class, 'destroy']);
    Route::post('/wishlist/{wishlist}/move-to-cart', [WishlistController::class, 'moveToCart']);
    
    // File Uploads
    Route::post('/upload', [FileController::class, 'upload']);
    Route::get('/files/{file}', [FileController::class, 'download']);
    Route::delete('/files/{file}', [FileController::class, 'delete']);
    
    // Account Management
    Route::post('/account/change-password', [UserController::class, 'changePassword']);
    Route::post('/account/delete', [UserController::class, 'deleteAccount']);
});

// Admin API Routes
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/analytics', [AdminDashboardController::class, 'analytics']);
    
    // Services Management
    Route::apiResource('services', AdminServiceController::class);
    Route::post('/services/{service}/variants', [AdminServiceController::class, 'createVariant']);
    Route::put('/services/{service}/variants/{variant}', [AdminServiceController::class, 'updateVariant']);
    Route::delete('/services/{service}/variants/{variant}', [AdminServiceController::class, 'deleteVariant']);
    
    // Orders Management
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
    Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/assign-editor', [AdminOrderController::class, 'assignEditor']);
    Route::post('/orders/{order}/refund', [AdminOrderController::class, 'processRefund']);
    Route::post('/orders/{order}/files', [AdminOrderController::class, 'uploadDeliverable']);
    
    // Users Management
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::post('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus']);
    
    // Content Management
    Route::apiResource('faqs', \App\Http\Controllers\Api\Admin\AdminFaqController::class);
    Route::apiResource('news', \App\Http\Controllers\Api\Admin\AdminNewsController::class);
    Route::apiResource('coupons', \App\Http\Controllers\Api\Admin\AdminCouponController::class);
    Route::apiResource('packages', \App\Http\Controllers\Api\Admin\AdminPackageController::class);
    
    // Reports
    Route::get('/reports/revenue', [AdminDashboardController::class, 'revenueReport']);
    Route::get('/reports/orders', [AdminDashboardController::class, 'ordersReport']);
    Route::get('/reports/services', [AdminDashboardController::class, 'servicesReport']);
    Route::get('/reports/export/{type}', [AdminDashboardController::class, 'exportReport']);
});

// Editor API Routes (for assigned editors)
Route::prefix('v1/editor')->middleware(['auth:sanctum', 'role:editor|admin'])->group(function () {
    
    // Assigned Orders
    Route::get('/orders', [OrderController::class, 'assignedOrders']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/message', [OrderController::class, 'addMessage']);
    Route::post('/orders/{order}/deliverables', [OrderController::class, 'uploadDeliverable']);
    Route::post('/orders/{order}/request-revision', [OrderController::class, 'requestRevision']);
    Route::post('/orders/{order}/mark-complete', [OrderController::class, 'markComplete']);
});

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});
