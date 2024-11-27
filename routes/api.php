<?php

use App\Http\Controllers\UserController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('users')->controller(UserController::class)->group(function () {
    Route::post('/getStarted', 'getStarted');
    Route::post('/register', 'register');
    Route::post('/register/{id}', 'register_for_guest');
    Route::post('/login', 'login');
    Route::middleware('check_auth:api')->group(function () {
        Route::post('/logout', 'logout');
        Route::get('/getProfile', 'getProfile');
        Route::post('/updateProfile', 'updateProfile');
        Route::post('/resetPassword', 'resetPassword');
    });
});
