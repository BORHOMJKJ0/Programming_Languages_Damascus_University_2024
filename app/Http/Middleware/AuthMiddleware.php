<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenInvalidException $ex) {
            return ResponseHelper::jsonResponse([], 'Invalid token', 401, false);
        } catch (TokenExpiredException $ex) {
            return ResponseHelper::jsonResponse([], 'Expired token, please refresh it', 401, false);
        } catch (JWTException $ex) {
            $guestMiddleware = new CheckGuestOrAuthenticated;

            return $guestMiddleware->handle($request, $next);
        }

        if (! $user) {
            return ResponseHelper::jsonResponse([], 'Unauthenticated', 401, false);
        }

        return $next($request);
    }
}
