<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\ExportController;
use App\Livewire\Billing\BillingHistory;
use App\Livewire\Billing\CreditPurchase;
use App\Livewire\Billing\SubscriptionManager;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Dashboard\OrganizationDashboard;
use App\Livewire\Notifications\NotificationList;
use App\Livewire\Pages\PageDetailView;
use App\Livewire\Profile\ProfileEdit;
use App\Livewire\Projects\ProjectCreate;
use App\Livewire\Projects\ProjectDashboard;
use App\Livewire\Projects\ProjectDictionary;
use App\Livewire\Projects\ProjectList;
use App\Livewire\Reports\ReportIndex;
use App\Livewire\Scans\ScanComparison;
use App\Livewire\Scans\ScanCreate;
use App\Livewire\Scans\ScanResults;
use App\Livewire\Settings\ApiTokens;
use App\Livewire\Settings\OrganizationDictionary;
use App\Livewire\Settings\SettingsIndex;
use App\Livewire\Team\TeamDepartments;
use App\Livewire\Team\TeamMembers;
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

// Test page with intentional errors (for development/QA)
Route::get('/test-errors', function () {
    return view('test-errors');
})->name('test-errors');

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

    // Organization Dashboard
    Route::get('/organization', OrganizationDashboard::class)->name('organization.dashboard');

    // Projects
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', ProjectList::class)->name('index');
        Route::get('/create', ProjectCreate::class)->name('create');
        Route::get('/{project}', ProjectDashboard::class)->name('show');
        Route::get('/{project}/dictionary', ProjectDictionary::class)->name('dictionary');
        Route::get('/{project}/pages/{url}', PageDetailView::class)->name('pages.show');
        Route::get('/{project}/issues', ProjectDashboard::class)->name('issues');
        Route::get('/{project}/schedules', ProjectDashboard::class)->name('schedules');
    });

    // Scans
    Route::prefix('scans')->name('scans.')->group(function () {
        Route::get('/new', ScanCreate::class)->name('create');
        Route::get('/{scan}', ScanResults::class)->name('show');
        Route::get('/{scan}/compare', ScanComparison::class)->name('compare');
        Route::get('/{scan}/export/pdf', [ExportController::class, 'scanPdf'])->name('export.pdf');
        Route::get('/{scan}/export/csv', [ExportController::class, 'scanCsv'])->name('export.csv');
        Route::get('/{scan}/compare/{baseline}/export/pdf', [ExportController::class, 'comparisonPdf'])->name('compare.export.pdf');
    });

    // Project Exports
    Route::prefix('projects/{project}/export')->name('projects.export.')->group(function () {
        Route::get('/issues.csv', [ExportController::class, 'projectIssuesCsv'])->name('issues.csv');
        Route::get('/issues.json', [ExportController::class, 'projectIssuesJson'])->name('issues.json');
        Route::get('/issues.pdf', [ExportController::class, 'projectIssuesPdf'])->name('issues.pdf');
        Route::get('/summary.pdf', [ExportController::class, 'projectSummaryPdf'])->name('summary.pdf');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', ReportIndex::class)->name('index');
    });

    // Team
    Route::prefix('team')->name('team.')->group(function () {
        Route::get('/members', TeamMembers::class)->name('members');
        Route::get('/departments', TeamDepartments::class)->name('departments');
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
        Route::get('/', SettingsIndex::class)->name('index');
        Route::get('/dictionary', OrganizationDictionary::class)->name('dictionary');
    });

    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', ProfileEdit::class)->name('edit');
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
