<?php

namespace App\Traits;

use App\Helpers\ResponseHelper;
use App\Models\Cart\Cart;
use Illuminate\Http\Exceptions\HttpResponseException;

trait AuthTrait
{
    public function checkOwnership($model, $modelType, $action)
    {
        $user = auth()->user();
        if ($model && $model->user_id !== $user->id) {
            throw new HttpResponseException(
                ResponseHelper::jsonResponse([],
                    "You are not authorized to {$action} this {$modelType} . This {$modelType} isn't for you",
                    403, false)
            );
        }
    }

    public function checkOwnershipForProducts($model, $modelType, $action)
    {
        $user = auth()->user();
        if ($model && $model->store->user_id !== $user->id) {
            throw new HttpResponseException(
                ResponseHelper::jsonResponse([],
                    "You are not authorized to {$action} this {$modelType} Because this isn't your product.",
                    403, false)
            );
        }
    }

    public function checkAdmin($modelType, $action)
    {
        $user = auth()->user();
        if ($user->role->role !== 'admin') {
            throw new HttpResponseException(
                ResponseHelper::jsonResponse([],
                    "Admin just {$action} this {$modelType}.",
                    403, false)
            );
        }
    }

    public function checkGuest()
    {
        $user = auth()->user();
        if ((! $user) || ($user->role->role === 'guest')) {
            throw new HttpResponseException(
                ResponseHelper::jsonResponse([],
                    'This permission is not available for guests.',
                    403, false)
            );
        }
    }

    public function checkCart($user_id)
    {
        $cart = Cart::where('user_id', $user_id)->first();

        if ($cart === null) {
            if (auth()->user()->role->role === 'super_admin') {
                throw new HttpResponseException(
                    ResponseHelper::jsonResponse(
                        [],
                        'Mr.SuperAdmin : There is no cart for this user.',
                        403,
                        false
                    )
                );
            } else {
                throw new HttpResponseException(
                    ResponseHelper::jsonResponse(
                        [],
                        'There is no cart, please login.',
                        403,
                        false
                    )
                );
            }
        }

        return $cart;
    }

    protected function checkSuperAdmin(): bool
    {
        return auth()->user() && auth()->user()->role->role === 'super_admin';
    }
}
