<?php

use App\Http\Controllers\V1\SharedLinkController;
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


