<?php

use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Store\StoreController;
use App\Http\Controllers\UserController;
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
Route::middleware('api')->group(function () {
    Route::apiResource('stores', StoreController::class);
    Route::apiResource('products', ProductController::class);
    Route::prefix('stores')->controller(StoreController::class)->group(function () {
        Route::get('/order/{column}/{direction}', 'orderBy');
    });

    Route::prefix('products')->controller(ProductController::class)->group(function () {
        Route::post('/{product}', 'update');
        Route::get('/order/{column}/{direction}', 'orderBy');
    });
    Route::prefix('categories')->controller(CategoryController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{category}', 'show');
        Route::get('/order/{column}/{direction}', 'orderBy');
    });
});

Route::prefix('users')->controller(UserController::class)->group(function () {
    Route::post('/refreshToken', 'refresh_token');
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
