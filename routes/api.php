<?php

use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Cart\CartItemsController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Image\ImageController;
use App\Http\Controllers\Order\Customer\CustomerOrderController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Order\Store\StoreOrderController;
use App\Http\Controllers\Product\FavoriteProductController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Store\StoreController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\UserController;
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
Route::middleware('check_auth:api')->group(function () {
    Route::get('/search/{model}', [SearchController::class, 'search']);
    Route::post('/create_product_with_images', [ProductController::class, 'create_product_with_images']);
    Route::prefix('stores')->controller(StoreController::class)->group(function () {
        Route::get('/pending_stores', 'PendingStores');
        Route::get('/my', 'getMy');
        Route::get('/order/{column}/{direction}', 'orderBy');
        Route::post('/{store}', 'update');
        Route::post('/Super_admin_response/{store}', 'handleSuperAdminResponse')
            ->name('superAdmin.storeApprovalResponse');
    });
    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::post('/logout', 'logout')->name('users.logout');
        Route::delete('/removeAccount', 'deleteUser')->name('users.delete');
        Route::get('/getProfile', 'getProfile')->name('users.getProfile');
        Route::post('/updateProfile', 'updateProfile')->name('users.updateProfile');
        Route::post('/resetPassword', 'resetPassword')->name('users.resetPassword');
    });
    Route::prefix('products')->controller(ProductController::class)->group(function () {
        Route::post('/{product}', 'update');
        Route::get('/order/{column}/{direction}', 'orderBy');
    });
    Route::prefix('products/favorites')->controller(FavoriteProductController::class)->group(function () {
        Route::get('/index/{user?}', 'index');
        Route::post('/store/{product}/{user?}', 'store');
        Route::delete('/destroy/{product}/{user?}', 'destroy');
    });
    Route::prefix('images')->controller(ImageController::class)->group(function () {
        Route::get('/{image}', 'show');
        Route::post('/', 'store');
        Route::post('/update/{image}', 'update');
        Route::delete('/{image}', 'destroy');
    });
    Route::prefix('categories')->controller(CategoryController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{category}', 'show');
        Route::get('/order/{column}/{direction}', 'orderBy');
    });
    Route::prefix('carts')->controller(CartController::class)->group(function () {
        Route::get('/{user?}', 'show');
        Route::post('/{user?}', 'store');
        Route::put('/{user?}', 'update');
        Route::delete('/{user?}', 'destroy');
    });
    Route::prefix('cart_items')->controller(CartItemsController::class)->group(function () {
        Route::get('/order/{column}/{direction}', 'orderBy');
    });
    Route::prefix('orders')->group(function () {
        Route::controller(OrderController::class)->group(function () {
            Route::get('/details/{order_id}', 'details');
        });
        Route::controller(CustomerOrderController::class)->group(function () {
            Route::post('/placeOrder', 'placeOrder');
            Route::get('/my', 'show');
            Route::get('my/completed', 'showCompleted');
            Route::post('/edit/{item}', 'edit');
            Route::delete('/delete/{item}', 'delete');
        });
        Route::controller(StoreOrderController::class)->group(function () {
            Route::get('/{store}', 'show');
            Route::get('/completed/{store}', 'showCompleted');
            Route::post('/accept/{item}', 'accept');
            Route::post('/reject/{item}', 'reject');
            Route::post('/ship/{item}', 'ship');
            Route::post('/deliver/{item}', 'deliver');
            Route::post('/cancel/byStore/{item}', 'cancel');
        });
    });
    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{notification}', 'show');
        Route::delete('/{notification}', 'destroy');
        Route::delete('/', 'destroy_all');
    });
    Route::apiResource('stores', StoreController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('cart_items', CartItemsController::class);
});

Route::prefix('users')->controller(UserController::class)->group(function () {
    Route::post('/refreshToken', 'refresh_token');
    Route::post('/getStarted', 'getStarted');
    Route::post('/register', 'register');
    Route::post('/register/{user}', 'register_for_guest');
    Route::post('/login', 'login');
});
