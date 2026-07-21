<?php

namespace App\Providers;

use App\Contracts\Payments\ExchangeRateProviderInterface;
use App\Models\Interest;
use App\Models\InterestCategory;
use App\Models\Test;
use App\Models\TestIntersetSelection;
use App\Observers\InterestCategoryObserver;
use App\Observers\InterestObserver;
use App\Observers\TestInterestSelectionObserver;
use App\Observers\TestObserver;
use App\Services\Payments\Providers\ExchangeRateApiProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ExchangeRateProviderInterface::class, ExchangeRateApiProvider::class);

        if (
            $this->app->environment('local') &&
            class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)
        ) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Interest::observe(InterestObserver::class);
        InterestCategory::observe(InterestCategoryObserver::class);
        Test::observe(TestObserver::class);
        TestIntersetSelection::observe(TestInterestSelectionObserver::class);
    }
}
