<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

Route::get('/', function () {
    return view('welcome');
});


// Email Verification Routes
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill();

    $user = $request->user();

    // Admins (and other staff who completed company setup) should land in their
    // admin panel after verifying. The /customer fallback is kept for future
    // customer-facing workflows.
    if ($user->hasRole('admin') || $user->hasRole('pending_company_setup') || $user->is_superadmin) {
        return redirect()->route('filament.admin.pages.dashboard');
    }

    return redirect('/customer');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register']);

    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);

    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

});

Route::post('/admin/logout', function (Request $request) {
    Auth::logout();
    return redirect('/login');
})->name('filament.admin.auth.logout');
Route::post('/customer/logout', function (Request $request) {
    Auth::logout();
    return redirect('/login');
})->name('filament.customer.auth.logout');

// Redirect panel login routes to central login
Route::get('/admin/login', fn () => redirect('/login'))->name('filament.admin.auth.login');
Route::get('/customer/login', fn () => redirect('/login'))->name('filament.customer.auth.login');
