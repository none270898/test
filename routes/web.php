<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;


Route::get('/debug-premium', function () {
    if (!auth()->check()) {
        return 'Not logged in';
    }
    
    $user = auth()->user();
    
    return response()->json([
        'user_id' => $user->id,
        'name' => $user->name,
        'premium' => $user->premium,
        'premium_expires_at' => $user->premium_expires_at,
        'isPremium_method' => $user->isPremium(),
        'current_time' => now(),
    ]);
})->middleware('auth')->name('debug.premium');
Route::get('/test-onesignal-send', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    
    $oneSignalService = new \App\Services\OneSignalService();
    
    try {
        $result = $oneSignalService->sendTestNotification();
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Test notification sent!' : 'Failed to send notification',
            'logs' => 'Check storage/logs/laravel.log for details'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'message' => 'Exception occurred while sending test notification'
        ]);
    }
})->name('test.onesignal.send');
// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.store');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    
    Route::get('/dashboard', [DashboardController::class, 'index'])
        
        ->name('dashboard');
     Route::get('/premium/upgrade', [PaymentController::class, 'showUpgrade'])->name('premium.upgrade');
    
    // Payment processing
    Route::post('/payment/checkout', [PaymentController::class, 'createCheckoutSession'])->name('payment.checkout');
    Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
    Route::get('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');
    
    // Billing management (Premium users only)
    Route::middleware('premium')->group(function () {
        Route::get('/payment/billing', [PaymentController::class, 'billingPortal'])->name('payment.billing');
        Route::post('/payment/cancel-subscription', [PaymentController::class, 'cancelSubscription'])->name('payment.cancel-subscription');
    });
});

Route::get('/', function () {
    return view('welcome');
})->name('home');
Route::post('/webhooks/stripe', [PaymentController::class, 'webhook'])->name('payment.webhook');
