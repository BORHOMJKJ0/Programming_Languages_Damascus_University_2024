<?php

namespace App\Exceptions;

use App\Helpers\ResponseHelper;
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
            $modelName = $exception->getModel();
            return ResponseHelper::jsonResponse([], "{$modelName} Not Found", 404, false);
        }

        if ($exception instanceof HttpResponseException) {
            return ResponseHelper::jsonResponse([], $exception->getMessage(), $exception->getCode(), false);
        }

        return parent::render($request, $exception);
    }
}
