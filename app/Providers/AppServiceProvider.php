<?php

namespace App\Providers;

use App\Models\Interest;
use App\Models\InterestCategory;
use App\Observers\InterestCategoryObserver;
use App\Observers\InterestObserver;
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
        Interest::observe(InterestObserver::class);
        InterestCategory::observe(InterestCategoryObserver::class);
    }
}
