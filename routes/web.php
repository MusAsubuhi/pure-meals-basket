<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/customer', function () {
    return view('auth.customer');
})->middleware(['auth', 'role:customer']);

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

use Illuminate\Foundation\Auth\EmailVerificationRequest;

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect('/customer');
})->middleware(['auth'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

/*
|--------------------------------------------------------------------------
| Customer-facing catalogue
|--------------------------------------------------------------------------
| Browsing and price quoting are public. Cart and request management
| require an authenticated customer.
*/
use App\Http\Controllers\CatalogueBrowseController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Fulfillment\FulfillmentController;
use App\Http\Controllers\Quotation\QuotationController;
use App\Http\Controllers\RequestController;

Route::prefix('catalogue')->name('catalogue.')->group(function () {
    Route::get('/', [CatalogueBrowseController::class, 'index'])->name('index');
    Route::get('/{category:slug}', [CatalogueBrowseController::class, 'category'])->name('category');
    Route::get('/products/{product:slug}', [CatalogueBrowseController::class, 'show'])->name('show');

    // Price estimation — public, POST body: {type, id, quantity, option_ids|option_value_ids}
    Route::post('/quote', [CatalogueBrowseController::class, 'quote'])->name('quote');
});

Route::middleware(['auth'])->group(function () {
    // Cart management
    Route::get('/request/cart', [CatalogueBrowseController::class, 'cart'])->name('request.cart');
    Route::post('/catalogue/add/{product}', [CatalogueBrowseController::class, 'add'])->name('catalogue.add');
    Route::delete('/request/cart/{itemKey}', [CatalogueBrowseController::class, 'remove'])->name('request.cart.remove');

    // Request management
    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/', [RequestController::class, 'index'])->name('index');
        Route::get('/checkout', [RequestController::class, 'checkout'])->name('checkout');
        Route::post('/submit', [RequestController::class, 'submit'])->name('submit');
        Route::post('/{clarification}/respond', [RequestController::class, 'respond'])->name('respond');
        Route::get('/{request}', [RequestController::class, 'show'])->name('show');
    });

    // Quotation actions
    Route::prefix('quotations')->name('quotations.')->group(function () {
        Route::get('/{quotation}', [QuotationController::class, 'show'])->name('show');
        Route::post('/{quotation}/accept', [QuotationController::class, 'accept'])->name('accept');
        Route::post('/{quotation}/decline', [QuotationController::class, 'decline'])->name('decline');
        Route::post('/{quotation}/changes', [QuotationController::class, 'requestChanges'])->name('changes');
    });

    // Order actions
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
    });

    // Payment actions
    Route::prefix('orders/{order}/payments')->name('payments.')->group(function () {
        Route::post('/mpesa', [PaymentController::class, 'initiateMpesa'])->name('mpesa');
        Route::post('/cash', [PaymentController::class, 'recordCash'])->name('cash');
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
        Route::get('/{payment}/status', [PaymentController::class, 'status'])->name('status');
        Route::post('/{payment}/confirm-cash', [PaymentController::class, 'confirmCash'])->name('confirm-cash');
    });

    // Fulfillment actions
    Route::prefix('fulfillments')->name('fulfillments.')->group(function () {
        Route::get('/', [FulfillmentController::class, 'index'])->name('index');
        Route::get('/{fulfillment}', [FulfillmentController::class, 'show'])->name('show');
    });
});
