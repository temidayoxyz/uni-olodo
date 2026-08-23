<?php

namespace App\Providers;

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

        // Surface N+1 query patterns loudly during development, never in production.
        if (! $this->app->isProduction()) {
            Model::preventLazyLoading();
        }
    }
}
