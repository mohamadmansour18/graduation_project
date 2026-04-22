<?php

use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\OnboardingController;
use App\Http\Controllers\V1\Auth\PasswordResetController;
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

    Route::prefix('user-mobile')->group(function () {

        Route::prefix('auth')->group(function () {

            Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:api-register');
            Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-login');
            Route::post('/verify-email' , [AuthController::class, 'verifyEmail'])->middleware('throttle:api-verify-email');

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

        });

        Route::middleware(['auth:api', 'role:mobile_user'])->group(function () {

        });

        Route::middleware(['auth:api' , 'role:owner'])->group(function () {

        });

        Route::middleware(['auth:api' , 'role:owner,supervisor'])->group(function () {

        });

    });


