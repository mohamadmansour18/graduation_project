<?php

use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\OnboardingController;
use App\Http\Controllers\V1\Auth\PasswordResetController;
use App\Http\Controllers\V1\Home\HomeController;
use App\Http\Controllers\V1\TestDiscovery\HomeTestDiscoveryController;
use App\Http\Controllers\V1\TestDiscovery\LabTestDiscoveryController;
use App\Http\Controllers\V1\Tests\LabController;
use App\Http\Controllers\V1\Tests\TestController;
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
            });
        });

        });



        Route::middleware(['jwt.auth.api' , 'role:owner'])->group(function () {

        });

        Route::middleware(['jwt.auth.api' , 'role:owner,supervisor'])->group(function () {

        });

    });




