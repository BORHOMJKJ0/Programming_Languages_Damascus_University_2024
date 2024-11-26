<?php

namespace App\Traits;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Exceptions\HttpResponseException;

trait AuthTrait
{
    public function checkOwnership($model, $modelType, $action, $type = 'user')
    {
        $user = auth()->user();

        if ($type === 'user' && $model->user_id !== $user->id) {
            throw new HttpResponseException(
                ResponseHelper::jsonResponse([],
                    "You are not authorized to {$action} this {$modelType}.",
                    403, false)
            );
        }

        if ($type === 'admin' && $user->role->role !== 'admin') {
            throw new HttpResponseException( ResponseHelper::jsonResponse([],
                    "You need admin privileges to {$action} this {$modelType}.",
                    403, false)
            );
        }
    }
}
