<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Super administrators pass every gate; all other roles go through explicit policies.
        Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);

        // Registration approval belongs to the registry.
        Gate::define('approve-registrations', fn (User $user) => $user->hasRole(UserRole::Registrar));

        // So does the results approval chain.
        Gate::define('approve-results', fn (User $user) => $user->hasRole(UserRole::Registrar));

        // Surface N+1 query patterns loudly during development, never in production.
        if (! $this->app->isProduction()) {
            Model::preventLazyLoading();
        }
    }
}
