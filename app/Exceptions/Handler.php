<?php

namespace App\Exceptions;

use App\Helpers\ResponseHelper;
use App\Models\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            $modelName = class_basename($exception->getModel());

            return ResponseHelper::jsonResponse([], "{$modelName} Not Found", 404, false);
        }

        if ($exception instanceof HttpResponseException) {
            return ResponseHelper::jsonResponse([], $exception->getMessage(), $exception->getCode(), false);
        }

        if ($exception instanceof \Kreait\Firebase\Exception\Messaging\NotFound || $exception instanceof \Kreait\Firebase\Exception\Messaging\InvalidMessage || $exception instanceof \Kreait\Firebase\Exception\Messaging\NotFound) {
            $user = User::where('id', auth()->id())->first();
            $super = User::whereHas('role', function ($query) {
                $query->where('role', 'super_admin');
            })->first();

            return ResponseHelper::jsonResponse([], "Requested Firebase entity was not found. Please check the provided data (fcm_token's).
            user->fcm_token={$user->fcm_token},
            Super_admin->fcm_token={$super->fcm_token}", 404, false);
        }

        return parent::render($request, $exception);
    }
}
