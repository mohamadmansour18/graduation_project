<?php

use App\Http\Controllers\V1\Auth\AuthController;
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

    Route::prefix('userMobile')->group(function () {
        Route::prefix('auth')->group(function () {

            Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:api-register');
            Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-login');

        });

        Route::middleware(['auth:api', 'role:mobile_user'])->group(function () {

        });

    });
    Route::middleware(['auth:api' , 'role:owner'])->group(function () {

    });

    Route::middleware(['auth:api' , 'role:owner,supervisor'])->group(function () {

    });
});
