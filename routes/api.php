<?php

use App\Http\Controllers\V1\Admin\AuthDashboardController;
use App\Http\Controllers\V1\Admin\HomeDashboardController;
use App\Http\Controllers\V1\Admin\TestDashboardController;
use App\Http\Controllers\V1\AiQuestionGeneration\AiQuestionGenerationController;
use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\OnboardingController;
use App\Http\Controllers\V1\Auth\PasswordResetController;
use App\Http\Controllers\V1\Folders\TestFolderController;
use App\Http\Controllers\V1\Home\HomeController;
use App\Http\Controllers\V1\Library\LibraryMaterialBookmarkController;
use App\Http\Controllers\V1\Library\LibraryMaterialController;
use App\Http\Controllers\V1\Library\LibraryMaterialDownloadController;
use App\Http\Controllers\V1\Library\LibraryMaterialLikeController;
use App\Http\Controllers\V1\Library\LibraryMaterialReportController;
use App\Http\Controllers\V1\Library\LibraryMaterialShareController;
use App\Http\Controllers\V1\Payments\TestPaymentController;
use App\Http\Controllers\V1\Profile\FollowController;
use App\Http\Controllers\V1\Profile\MyProfileController;
use App\Http\Controllers\V1\Profile\PublicProfileController;
use App\Http\Controllers\V1\TestDiscovery\HomeTestDiscoveryController;
use App\Http\Controllers\V1\TestDiscovery\LabTestDiscoveryController;
use App\Http\Controllers\V1\Tests\LabController;
use App\Http\Controllers\V1\Tests\TestBookmarkController;
use App\Http\Controllers\V1\Tests\TestController;
use App\Http\Controllers\V1\Tests\TestDownloadController;
use App\Http\Controllers\V1\Tests\TestFilterController;
use App\Http\Controllers\V1\Tests\TestLikeController;
use App\Http\Controllers\V1\Tests\TestReportController;
use App\Http\Controllers\V1\Tests\TestReportReviewController;
use App\Http\Controllers\V1\Tests\TestReviewController;
use App\Http\Controllers\V1\Tests\TestRevisionRequestController;
use App\Http\Controllers\V1\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

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

            Route::get('/logout' , [AuthController::class , 'logout']);

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
                Route::get('/ai-question-generation/daily-limit' , [AiQuestionGenerationController::class, 'aiGenerationDailyLimit']);

                Route::middleware('idempotency')->group(function () {
                    Route::post('/create-test' , [LabController::class , 'store'])->middleware('throttle:3,2');
                    Route::post('/ai-question-generations', [AiQuestionGenerationController::class, 'store']);
                });
                Route::get('/ai-question-generations/{id}', [AiQuestionGenerationController::class, 'show']);
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

                Route::get('/tests/filter', [TestFilterController::class, 'filter']);

                Route::middleware('throttle:4,3')->group(function () {
                    Route::post('/like/{testId}' , [TestLikeController::class , 'like']);
                    Route::delete('/unlike/{testId}' , [TestLikeController::class , 'unlike']);

                    Route::post('/bookmark/{testId}' , [TestBookmarkController::class , 'bookmark']);
                    Route::delete('/unbookmark/{testId}' , [TestBookmarkController::class , 'unbookmark']);

                    Route::post('/add/review/{testId}' , [TestReviewController::class , 'store']);
                    Route::post('/update/review/{testId}' , [TestReviewController::class , 'update']);
                    Route::delete('/delete/review/{testId}' , [TestReviewController::class , 'destroy']);

                    Route::post('/add/feedback-on-review/{reviewId}' , [TestReviewController::class ,'storeFeedback']);
                    Route::delete('/delete/feedback-on-review/{reviewId}' , [TestReviewController::class ,'deleteFeedback']);

                    Route::post('/attempts/{testId}' , [TestController::class, 'storeAttempt']);

                    Route::middleware('idempotency')->group(function () {
                        Route::post('/reports/{testId}' , [TestReportController::class , 'store']);
                        Route::post('/reports/review/{reviewId}' , [TestReportReviewController::class , 'store']);
                        Route::post('/payments/stripe/{testId}' , [TestPaymentController::class , 'createStripeCheckoutSession']);
                        Route::delete('/delete/test/{testId}', [TestController::class, 'destroy']);
                        Route::post('/update/test/{testId}' , [TestController::class, 'update']);
                    });

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

                Route::get('/overview/{userId}' , [PublicProfileController::class , 'show']);
                Route::get('/test/{userId}' , [PublicProfileController::class , 'tests']);
                Route::get('/folder/{userId}' , [PublicProfileController::class , 'folders']);
                Route::get('/content/{userId}' , [PublicProfileController::class , 'materials']);

                Route::get('/folder-details/{folderId}' , [PublicProfileController::class , 'folderContent']);
                Route::get('/academic-certificate/{userId}' , [PublicProfileController::class , 'academicCertificate']);

                Route::get('/share-link/{userId}' , [PublicProfileController::class , 'shareLink']);
                Route::get('/shared/{slug}' , [PublicProfileController::class , 'resolveShareSlug']);

                Route::get('/followers/{userId}', [PublicProfileController::class, 'followers']); //يلي متابعيني
                Route::get('/following/{userId}', [PublicProfileController::class, 'following']); //يلي انا متابعهن

                Route::middleware('throttle:4,2')->group(function () {
                    Route::post('/follow/{userId}' , [FollowController::class , 'follow']);
                    Route::delete('/unfollow/{userId}' , [FollowController::class , 'unfollow']);

                    Route::post('/folder-bookmarks/{folder}', [PublicProfileController::class, 'bookmarkFolder']);
                    Route::delete('/folder-bookmarks/{folder}', [PublicProfileController::class, 'unbookmarkFolder']);
                });
            });

            //LIBRARY
            Route::prefix('library')->group(function () {
                Route::get('/show', [LibraryMaterialController::class, 'index']);
                Route::get('/search' , [LibraryMaterialController::class , 'search']);
                Route::get('/library-materials-details/other/{materialId}' , [LibraryMaterialController::class , 'showDetails']);
                Route::get('/library-materials-details/my-public/{materialId}' , [LibraryMaterialController::class , 'showMyDetails']);

                Route::get('/like-list/{materialId}' , [LibraryMaterialLikeController::class , 'likedUsers']);
                Route::get('/bookmark-list/{materialId}' , [LibraryMaterialBookmarkController::class , 'bookmarkedUsers']);

                Route::get('/download/{materialId}' , [LibraryMaterialDownloadController::class , 'download'])->middleware('throttle:8,3');
                Route::get('/share-link/{materialId}' , [LibraryMaterialShareController::class , 'generate']);
                Route::get('/shared/{slug}' , [LibraryMaterialShareController::class , 'resolve']);

                Route::get('/similar/{materialId}' , [LibraryMaterialController::class, 'similar']);

                Route::middleware('throttle:4,3')->group(function () {
                    Route::post('/like/{libraryMaterial}', [LibraryMaterialLikeController::class, 'like']);
                    Route::delete('/unlike/{libraryMaterial}', [LibraryMaterialLikeController::class, 'unlike']);

                    Route::post('/bookmark/{libraryMaterial}', [LibraryMaterialBookmarkController::class, 'bookmark']);
                    Route::delete('/unbookmark/{libraryMaterial}', [LibraryMaterialBookmarkController::class, 'unbookmark']);
                });

                Route::middleware('idempotency')->group(function () {
                    Route::post('/create-content' , [LibraryMaterialController::class , 'store'])->middleware('throttle:3,2');
                    Route::post('/reports/{libraryMaterial}' , [LibraryMaterialReportController::class , 'store']);
                    Route::delete('/delete/material/{libraryMaterial}', [LibraryMaterialController::class, 'destroy']);
                    Route::post('/update/material/{libraryMaterial}' , [LibraryMaterialController::class, 'update']);
                });
            });

            //MY_PROFILE
            Route::prefix('my-profile')->group(function () {
                Route::get('/basic-info/{userId}' , [MyProfileController::class , 'myBasicInfo']);
                Route::get('/bookmarks', [MyProfileController::class, 'bookmarks']);

                Route::get('/tests/{userId}' , [MyProfileController::class , 'myCreatedTests']);
                Route::post('/test/search' , [MyProfileController::class, 'searchTests']);

                Route::get('/library/{userId}' , [MyProfileController::class , 'myLibraryMaterials']);
                Route::get('/library-material/search' , [MyProfileController::class , 'search']);

                Route::get('/folder/{userId}' , [MyProfileController::class , 'folders']);


                Route::middleware(['idempotency' , 'throttle:3,2'])->group(function () {
                    Route::post('/update/basic-info/{userId}' , [MyProfileController::class, 'updatePersonalInformation']);
                    Route::post('/update/academic-info/{userId}' , [MyProfileController::class, 'updateAcademicInformation']);
                    Route::post('/update/scientific-interests/{userId}' , [MyProfileController::class, 'updateScientificInterests']);
                    Route::post('/update/photo/{userId}' , [MyProfileController::class, 'updatePhoto']);
                    Route::delete('/delete/photo/{userId}' , [MyProfileController::class, 'deletePhoto']);
                });
            });

            //FOLDER
            Route::prefix('folder')->group(function () {

                Route::get('/folder-content/{folder}' , [TestFolderController::class , 'folderTests']);

                Route::middleware(['idempotency' , 'throttle:3,4'])->group(function () {
                    Route::post('/create' , [TestFolderController::class , 'storeFolder']);
                    Route::delete('/delete/{folderId}' , [TestFolderController::class , 'deleteFolder' ]);
                    Route::post('/update/{folderId}' , [TestFolderController::class , 'updateFolder']);
                });
            });

        });



        });


    Route::prefix('dashboard')->group(function () {

        Route::prefix('auth')->group(function () {
            Route::post('/login', [AuthDashboardController::class, 'login'])->middleware('throttle:api-login');

            Route::middleware('throttle:api-reset-password')->group(function () {
                Route::post('/forgot-password/request-otp', [PasswordResetController::class, 'requestPasswordResetOtp']);
                Route::post('/forgot-password/verify-otp', [PasswordResetController::class, 'verifyPasswordResetOtp']);
                Route::post('/forgot-password/resend-otp', [PasswordResetController::class, 'resendPasswordResetOtp']);
                Route::post('/forgot-password/reset' , [PasswordResetController::class , 'resetPassword']);
            });
        });

        Route::middleware(['jwt.auth.api' , 'role:owner,supervisor'])->group(function () {
            Route::get('/logout' , [AuthController::class , 'logout']);

            //HOME
            Route::prefix('home')->group(function () {
                Route::get('/test-yearly-activity' , [HomeDashboardController::class , 'yearlyTestActivity']);
                Route::get('/library_stats' , [HomeDashboardController::class , 'usersAndLibraryStats']);
            });

            //TEST_MANAGEMENT
            Route::prefix('test-management')->group(function () {
                Route::get('/management-board' , [TestDashboardController::class , 'managementBoard']);
                Route::get('/management-board/details/{testId}' , [TestDashboardController::class , 'managementTestDetails']);
                Route::get('/questions/{testId}' , [TestDashboardController::class , 'content']);
                Route::get('/questions-samples/{testId}' , [TestDashboardController::class , 'questionsSamples']);
                Route::get('/reviews/{testId}' , [TestDashboardController::class , 'managementTestReviews']);
                Route::get('/status-history/{tetsId}' , [TestDashboardController::class , 'managementTestStatusHistory']);
                Route::get('/reports/{testId}' , [TestDashboardController::class , 'managementTestReports']);
                Route::get('/ai-evaluation/status/{evaluationId}' , [TestDashboardController::class , 'aiEvaluationStatus']);

                Route::middleware('idempotency')->group(function () {
                    Route::post('/approve/{testId}' , [TestDashboardController::class , 'approveManagementTest']);
                    Route::post('/delete/{testId}' , [TestDashboardController::class , 'deleteManagementTest']);
                    Route::post('/need-revision/{testId}' , [TestDashboardController::class , 'requestManagementTestRevisions']);
                    Route::put('/update/need-revision/{testId}' , [TestDashboardController::class , 'updateManagementTestRevisionRequests']);
                    Route::post('/ai-evaluation/{testId}' , [TestDashboardController::class , 'requestAiEvaluation']);

                    Route::delete('/delete/review/{reviewId}' , [TestDashboardController::class , 'deleteManagementTestReview']);
                });
            });

        });

        Route::middleware(['jwt.auth.api' , 'role:owner'])->group(function () {
            Route::get('/financial-stats' , [HomeDashboardController::class , 'financialStats']);

        });



    });


});

/*
 * TODO :
 * في الـ APIs التي تعرض الاختبارات العامة لا تستخدم withTrashed() أبدا
 أما API مشتريات المستخدم لاحقاً، نستخدم withTrashed() فقط حتى يرى المشتري الاختبار المدفوع المحذوف Soft Delete

TODO: تعديل اختبار فيه مشكلة لازم تشوفها وتحلها
 * */
