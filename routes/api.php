<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
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
    Route::get('/test' , function (){
        return response()->json(['message' => 'test']);
    })->middleware('throttle:api-global');
    Route::post('/refresh', [AuthController::class, 'refresh']);
});
