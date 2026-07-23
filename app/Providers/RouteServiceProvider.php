<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api-global', function (Request $request) {
            $user = $request->user();

            $key = $user
                ? 'user:' . $user->id
                : 'ip:' . $request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('api-login' , function (Request $request) {
            $key = 'login:' . ($request->ip());
            return Limit::perMinutes(5, 3)->by($key);
        });

        RateLimiter::for('api-register' , function (Request $request) {
            $key = 'register:' . ($request->ip());
            return Limit::perMinute(3)->by($key);
        });

        RateLimiter::for('api-verify-email' , function (Request $request) {
            $key = 'register:' . ($request->ip());
            return Limit::perMinute(2)->by($key);
        });

        RateLimiter::for('api-onboarding' , function (Request $request) {
            $key = 'onboarding:' . ($request->ip());
            return Limit::perMinute(2)->by($key);
        });

        RateLimiter::for('api-reset-password' , function (Request $request) {
            $key = 'reset-password:' . ($request->ip());
            return Limit::perMinutes(3,3)->by($key);
        });

        RateLimiter::for('api-search' , function (Request $request) {
            $user = $request->user();

            $key = $user
                ? 'search-user:' . $user->id
                : 'search-ip:' . $request->ip();

            return Limit::perMinute(20)->by($key);
        });
    }
}
