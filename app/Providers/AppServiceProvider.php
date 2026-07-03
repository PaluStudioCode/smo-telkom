<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
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
        Gate::before(fn (User $user, string $ability) => $user->isSuperAdmin() ? true : null);

        Gate::define('dashboard.view_all', fn (User $user) => $user->isSuperAdmin());
        Gate::define('dashboard.view_related', fn (User $user) => true);

        foreach (['order_status', 'order_edk', 'complete'] as $module) {
            Gate::define($module.'.view', fn (User $user) => true);
            Gate::define($module.'.create', fn (User $user) => $user->isAdminInputer());
            Gate::define($module.'.update', fn (User $user) => $user->isAdminInputer());
            Gate::define($module.'.delete', fn (User $user) => $user->isAdminInputer());
        }

        Gate::define('complete.approve', fn (User $user) => false);
        Gate::define('complete.reject', fn (User $user) => false);
        Gate::define('complete.request_revision', fn (User $user) => false);

        foreach (['user.view', 'user.create', 'user.update', 'user.delete'] as $permission) {
            Gate::define($permission, fn (User $user) => false);
        }

        Gate::define('profile.update_self', fn (User $user) => true);
        Gate::define('password.update_self', fn (User $user) => true);

        Vite::prefetch(concurrency: 3);
    }
}
