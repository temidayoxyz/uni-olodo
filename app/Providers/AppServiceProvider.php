<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Payments\DevGateway;
use App\Services\Payments\PaymentProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Gateway abstraction: swap DevGateway for a production provider (e.g. Paystack)
        // without touching controllers or settlement logic.
        $this->app->bind(PaymentProvider::class, DevGateway::class);
    }

    public function boot(): void
    {
        // Super administrators pass every gate; all other roles go through explicit policies.
        Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);

        // Registration approval belongs to the registry.
        Gate::define('approve-registrations', fn (User $user) => $user->hasRole(UserRole::Registrar));

        // So does the results approval chain.
        Gate::define('approve-results', fn (User $user) => $user->hasRole(UserRole::Registrar));

        // Academic structure (faculties → programmes → courses, calendar): registry.
        Gate::define('manage-structure', fn (User $user) => $user->hasRole(UserRole::Registrar));

        // Bursary: invoices and manual payment verification.
        Gate::define('manage-payments', fn (User $user) => $user->hasRole(UserRole::FinanceOfficer));

        // Users & roles: super administrator only (Gate::before already grants them).
        Gate::define('manage-users', fn () => false);

        // Surface N+1 query patterns loudly during development, never in production.
        if (! $this->app->isProduction()) {
            Model::preventLazyLoading();
        }
    }
}
