<?php

use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\OnboardingController;
use App\Http\Controllers\V1\Auth\PasswordResetController;
use App\Http\Controllers\V1\Home\HomeController;
use App\Http\Controllers\V1\Payments\TestPaymentController;
use App\Http\Controllers\V1\Profile\FollowController;
use App\Http\Controllers\V1\TestDiscovery\HomeTestDiscoveryController;
use App\Http\Controllers\V1\TestDiscovery\LabTestDiscoveryController;
use App\Http\Controllers\V1\Tests\LabController;
use App\Http\Controllers\V1\Tests\TestBookmarkController;
use App\Http\Controllers\V1\Tests\TestController;
use App\Http\Controllers\V1\Tests\TestDownloadController;
use App\Http\Controllers\V1\Tests\TestLikeController;
use App\Http\Controllers\V1\Tests\TestReportController;
use App\Http\Controllers\V1\Tests\TestReportReviewController;
use App\Http\Controllers\V1\Tests\TestReviewController;
use App\Http\Controllers\V1\Tests\TestRevisionRequestController;
use App\Http\Controllers\V1\Webhooks\StripeWebhookController;
use App\Models\Test;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//'auth:api'

Route::prefix('v1')->middleware(['force.json' , 'request.id' ])->group(function () {

    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/webhooks/stripe' , StripeWebhookController::class);

    Route::prefix('user-mobile')->group(function () {

        Route::prefix('auth')->group(function () {

            //LOGIN && REGISTER
            Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:api-register');
            Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-login');
            Route::post('/verify-email' , [AuthController::class, 'verifyEmail'])->middleware('throttle:api-verify-email');
            Route::post('/reset-send-otp' , [AuthController::class , 'resetPassword'])->middleware('throttle:2,5');

            //ONBOARDING
            Route::middleware('throttle:api-onboarding')->group(function () {
                Route::post('/onboarding/discovery-source' , [OnboardingController::class , 'saveDiscoverySource']);
                Route::Post('/onboarding/education-level' , [OnboardingController::class , 'saveEducationLevel']);
                Route::post('/onboarding/school-stage' , [OnboardingController::class , 'saveSchoolStage']);
                Route::post('/onboarding/university-profile' , [OnboardingController::class , 'saveUniversityProfile']);
                Route::post('/onboarding/graduate-academic-profile' , [OnboardingController::class , 'saveGraduateAcademicProfile']);
                Route::post('/onboarding/user-interests' , [OnboardingController::class , 'saveUserInterests']);
            });

            Route::get('/onboarding/interests' , [OnboardingController::class , 'getInterestCategoriesWithInterests']);
            Route::Post('/onboarding/progress-preview' , [OnboardingController::class , 'getOnboardingProgressPreview']);

            //RESET PASSWORD
            Route::middleware('throttle:api-reset-password')->group(function () {
                Route::post('/forgot-password/request-otp', [PasswordResetController::class, 'requestPasswordResetOtp']);
                Route::post('/forgot-password/verify-otp', [PasswordResetController::class, 'verifyPasswordResetOtp']);
                Route::post('/forgot-password/resend-otp', [PasswordResetController::class, 'resendPasswordResetOtp']);
                Route::post('/forgot-password/reset' , [PasswordResetController::class , 'resetPassword']);
            });

            });

        Route::middleware(['jwt.auth.api', 'role:mobile_user'])->group(function () {

            //HOME
            Route::prefix('home')->group(function () {
                Route::get('/recommended-tests' , [HomeTestDiscoveryController::class , 'index']);
                Route::get('/recommended-interests' , [HomeController::class , 'getInterests']);
                Route::get('/recommended-users' , [HomeController::class , 'topTestCreators']);

                Route::get('/all-interests' , [HomeController::class , 'scientificInterests']);
                Route::get('/test-by-interest/{interestId}' , [HomeController::class , 'testsByInterest']);
                Route::post('/search-test-by-interest' , [HomeController::class , 'searchTests'])->middleware('throttle:api-search');
            });

            //LABORATORY
            Route::prefix('lab')->group(function () {
                Route::get('/recommended-tests', [LabTestDiscoveryController::class, 'index']);
                Route::post('/search' , [LabController::class, 'searchTests'])->middleware('throttle:api-search');
            });

            //TESTS
            Route::prefix('test')->group(function () {
                Route::get('/tests-details/other/{testId}', [TestController::class , 'show']);
                Route::get('/tests-details/other/sample/{testId}', [TestController::class , 'previewSampleQuestions']);
                Route::get('/test-details/reviews/{testId}', [TestController::class , 'reviews']);

                Route::get('/tests-details/my-private/{testId}', [TestController::class , 'showMyPrivateTestDetails']);

                Route::get('/tests-details/my-public/{testId}', [TestController::class , 'showMyPublicTestDetails']);
                Route::get('/tests-details/my-public/status-history/{testId}' , [TestController::class , 'statusHistory']);
                Route::get('/tests-details/my-public/status-history/{testId}/revision-request/{roundId}' , [TestRevisionRequestController::class , 'revisionRequestsByRound']);
                Route::get('/tests-details/my-public/reviews/{testId}' , [TestController::class , 'showMyTestReviews']);

                Route::middleware('throttle:5,2')->group(function () {
                    Route::post('/like/{testId}' , [TestLikeController::class , 'like']);
                    Route::delete('/unlike/{testId}' , [TestLikeController::class , 'unlike']);

                    Route::post('/bookmark/{testId}' , [TestBookmarkController::class , 'bookmark']);
                    Route::delete('/unbookmark/{testId}' , [TestBookmarkController::class , 'unbookmark']);

                    Route::post('/add/review/{testId}' , [TestReviewController::class , 'store']);
                    Route::post('/update/review/{testId}' , [TestReviewController::class , 'update']);
                    Route::delete('/delete/review/{testId}' , [TestReviewController::class , 'destroy']);

                    Route::post('/add/feedback-on-review/{reviewId}' , [TestReviewController::class ,'storeFeedback']);
                    Route::delete('/delete/feedback-on-review/{reviewId}' , [TestReviewController::class ,'deleteFeedback']);

                    Route::middleware('idempotency')->group(function () {
                        Route::post('/reports/{testId}' , [TestReportController::class , 'store']);
                        Route::post('/reports/review/{reviewId}' , [TestReportReviewController::class , 'store']);
                    });

                    Route::post('/payments/stripe/{testId}' , [TestPaymentController::class , 'createStripeCheckoutSession'])->middleware('idempotency.payment');

                });

                Route::get('/download/{testId}' , [TestDownloadController::class , 'downloadPdf'])->middleware('throttle:8,3');
                Route::get('/like-list/{testId}' , [TestLikeController::class , 'likedUsers']);
                Route::get('/bookmark-list/{testId}' , [TestBookmarkController::class , 'bookmarkedUsers']);
                Route::get('/share-link/{testId}' , [TestController::class , 'shareLink']);
                Route::get('/shared/{slug}' , [TestController::class , 'showByShareSlug']);
                Route::get('/content/{testId}' , [TestController::class , 'content']);
            });

            //USER PROFILE
            Route::prefix('users-profile')->group(function () {
                Route::middleware('throttle:4,2')->group(function () {
                    Route::post('/follow/{userId}' , [FollowController::class , 'follow']);
                    Route::delete('/unfollow/{userId}' , [FollowController::class , 'unfollow']);
                });
            });
        });

        });



        Route::middleware(['jwt.auth.api' , 'role:owner'])->group(function () {

        });

        Route::middleware(['jwt.auth.api' , 'role:owner,supervisor'])->group(function () {

        });

    });




