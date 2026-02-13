<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\ExportController;
use App\Livewire\Billing\BillingHistory;
use App\Livewire\Billing\CreditPurchase;
use App\Livewire\Billing\SubscriptionManager;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Notifications\NotificationList;
use App\Livewire\Projects\ProjectList;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\Scans\ScanComparison;
use App\Livewire\Scans\ScanResults;
use App\Livewire\Settings\ApiTokens;
use App\Livewire\Webhooks\WebhookDeliveries;
use App\Livewire\Webhooks\WebhookEndpoints;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| OAuth Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    // Google OAuth
    Route::get('/google', [SocialiteController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/google/callback', [SocialiteController::class, 'handleGoogleCallback']);

    // Microsoft OAuth
    Route::get('/microsoft', [SocialiteController::class, 'redirectToMicrosoft'])->name('auth.microsoft');
    Route::get('/microsoft/callback', [SocialiteController::class, 'handleMicrosoftCallback']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Projects
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', ProjectList::class)->name('index');
        Route::get('/create', function () {
            return view('dashboard'); // Placeholder - will be CreateProject Livewire component
        })->name('create');
        Route::get('/{project}', ProjectShow::class)->name('show');
    });

    // Scans
    Route::prefix('scans')->name('scans.')->group(function () {
        Route::get('/new', function () {
            return view('dashboard'); // Placeholder
        })->name('create');
        Route::get('/{scan}', ScanResults::class)->name('show');
        Route::get('/{scan}/compare', ScanComparison::class)->name('compare');
        Route::get('/{scan}/export/pdf', [ExportController::class, 'scanPdf'])->name('export.pdf');
        Route::get('/{scan}/compare/{baseline}/export/pdf', [ExportController::class, 'comparisonPdf'])->name('compare.export.pdf');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', function () {
            return view('dashboard'); // Placeholder
        })->name('index');
    });

    // Team
    Route::prefix('team')->name('team.')->group(function () {
        Route::get('/members', function () {
            return view('dashboard'); // Placeholder
        })->name('members');

        Route::get('/departments', function () {
            return view('dashboard'); // Placeholder
        })->name('departments');
    });

    // API & Webhooks
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/tokens', ApiTokens::class)->name('tokens');
        Route::get('/webhooks', WebhookEndpoints::class)->name('webhooks');
        Route::get('/webhooks/{endpoint}/deliveries', WebhookDeliveries::class)->name('webhooks.deliveries');
        Route::get('/webhooks/deliveries', WebhookDeliveries::class)->name('webhooks.deliveries.all');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', function () {
            return view('dashboard'); // Placeholder
        })->name('index');
    });

    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', function () {
            return view('dashboard'); // Placeholder
        })->name('edit');
    });

    // Billing
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', SubscriptionManager::class)->name('index');
        Route::get('/credits', CreditPurchase::class)->name('credits');
        Route::get('/history', BillingHistory::class)->name('history');
        Route::get('/success', function () {
            return redirect()->route('billing.index')->with('success', 'Subscription updated successfully!');
        })->name('success');
        Route::get('/credits/success', function () {
            return redirect()->route('billing.credits')->with('success', 'Credits purchased successfully!');
        })->name('credits.success');
    });

    // Notifications
    Route::get('/notifications', NotificationList::class)->name('notifications.index');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes (will be replaced by Fortify)
|--------------------------------------------------------------------------
*/

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Stripe Webhooks
|--------------------------------------------------------------------------
*/

Route::post(
    '/stripe/webhook',
    [\App\Http\Controllers\Webhooks\StripeWebhookController::class, 'handleWebhook']
)->name('stripe.webhook');
