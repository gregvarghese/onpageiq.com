<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use App\Services\Analysis\ContentAnalyzer;
use App\Services\Browser\BrowserServiceManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BrowserServiceManager::class);
        $this->app->singleton(ContentAnalyzer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super Admin bypasses all permission checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole(Role::SuperAdmin->value) ? true : null;
        });

        // Pulse dashboard authorization - only Super Admins
        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole(Role::SuperAdmin->value);
        });
    }
}
