<?php

namespace App\Traits;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Exceptions\HttpResponseException;

trait AuthTrait
{
    public function checkOwnership($model, $modelType, $action, $type = 'user')
    {
        $user = auth()->user();

        if ($model !== null && $type === 'admin' && $model->user_id !== $user->id) {
            throw new HttpResponseException(
                ResponseHelper::jsonResponse([],
                    "You are not authorized to {$action} this {$modelType}.",
                    403, false)
            );
        }
    }

    public function checkAccount($model, $modelType, $action, $type = 'admin')
    {
        $user = auth()->user();
        if ($type === 'admin' && $user->role->role !== $type) {
            throw new HttpResponseException(
                ResponseHelper::jsonResponse([],
                    "Admin just {$action} this {$modelType}.",
                    403, false)
            );
        } elseif ($type === 'guest' && $user->role->role === 'guest') {
            throw new HttpResponseException(
                ResponseHelper::jsonResponse([],
                    'This permission is not available for guests.',
                    403, false)
            );
        }
    }
}
