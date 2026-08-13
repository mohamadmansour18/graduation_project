<?php

use App\Http\Controllers\V1\SharedLinkController;
use App\Http\Controllers\PaymentReturnController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::view('/', 'landing.index')
    ->name('landing');

Route::get('/payment/return/success', [PaymentReturnController::class, 'success'])
    ->name('payments.return.success');
Route::get('/payment/return/cancel', [PaymentReturnController::class, 'cancel'])
    ->name('payments.return.cancel');

//TEST
Route::get('/share/tests/{slug}', [SharedLinkController::class, 'test'])
    ->name('share.tests.show');

Route::get('/app-not-installed', function () {
    return view('share.app-not-installed');
});

//LIBRARY
Route::get('/share/library/{slug}', [SharedLinkController::class, 'libraryMaterial'])
    ->name('share.library.show');

Route::get('/app-not-installed', function () {
    return view('share.app-not-installed');
});

//PROFILE
Route::get('/share/profiles/{slug}', [SharedLinkController::class, 'profile'])
    ->name('share.profiles.show');

Route::get('/app-not-installed', function () {
    return view('share.app-not-installed');
});
