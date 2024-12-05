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
        if ($user->role->role === 'guest') {
            throw new HttpResponseException(
                ResponseHelper::jsonResponse([],
                    'This permission is not available for guests.',
                    403, false)
            );
        }
    }

    public function checkCart()
    {
        $cart = Cart::where('user_id', auth()->id())->first();
        if ($cart === null) {
            throw new HttpResponseException(
                ResponseHelper::jsonResponse([],
                    'There is no cart ,Please login.',
                    403, false)
            );
        }
    }
}
