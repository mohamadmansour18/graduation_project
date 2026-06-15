<?php

namespace App\Providers;

use App\Events\LibraryMaterialDeletedByOwner;
use App\Events\LibraryMaterialLikeChanged;
use App\Events\TestBookmarkStateChanged;
use App\Events\TestDeleted;
use App\Events\TestDownloaded;
use App\Events\TestLikeStateChanged;
use App\Events\TestPurchasePaid;
use App\Events\TestReviewStateChanged;
use App\Events\UserDiscoverySourceSaved;
use App\Events\UserOnboardingCompleted;
use App\Listeners\UpdateAdminFinancialStatsAfterTestPurchase;
use App\Listeners\UpdateAdminLibraryMaterialMonthlyStats;
use App\Listeners\UpdateAdminLibraryOnMaterialDeleted;
use App\Listeners\UpdateTestBookmarkSummaryTables;
use App\Listeners\UpdateTestDownloadSummaryTables;
use App\Listeners\UpdateTestLikeSummaryTables;
use App\Listeners\UpdateTestReviewSummaryTables;
use App\Listeners\UpdateTestSummariesAfterDeletion;
use App\Listeners\UpdateUserStatsByDiscoverySource;
use App\Listeners\UpdateUserStatsSummaryAfterOnboardingCompleted;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        TestLikeStateChanged::class => [
            UpdateTestLikeSummaryTables::class,
        ],

        TestBookmarkStateChanged::class => [
            UpdateTestBookmarkSummaryTables::class
        ],

        TestDownloaded::class => [
            UpdateTestDownloadSummaryTables::class,
        ],

        TestReviewStateChanged::class => [
            UpdateTestReviewSummaryTables::class,
        ],
        TestDeleted::class => [
            UpdateTestSummariesAfterDeletion::class,
        ],

        LibraryMaterialLikeChanged::class => [
            UpdateAdminLibraryMaterialMonthlyStats::class,
        ],

        LibraryMaterialDeletedByOwner::class => [
            UpdateAdminLibraryOnMaterialDeleted::class,
        ],

        TestPurchasePaid::class => [
            UpdateAdminFinancialStatsAfterTestPurchase::class,
        ],

        UserDiscoverySourceSaved::class => [
            UpdateUserStatsByDiscoverySource::class,
        ],

        UserOnboardingCompleted::class => [
            UpdateUserStatsSummaryAfterOnboardingCompleted::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
