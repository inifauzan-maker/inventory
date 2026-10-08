<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserHasRole;
use App\Models\ProcurementRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::aliasMiddleware('role', EnsureUserHasRole::class);

        foreach (User::roleLabels() as $role => $label) {
            Gate::define($role, fn (User $user) => $user->hasRole($role));
        }

        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            if (($arguments[0] ?? null) instanceof ProcurementRequest || ($arguments[0] ?? null) === ProcurementRequest::class) {
                return null;
            }
            if ($user->isSuperAdmin()) {
                return true;
            }

            return null;
        });
    }
}
