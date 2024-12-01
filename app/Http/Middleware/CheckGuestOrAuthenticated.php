<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;

class CheckGuestOrAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            return $next($request);
        }

        if ($request->query('guest') === 'true') {
            return $next($request);
        }

        return ResponseHelper::jsonResponse([],
            'You must either register or explicitly enter as a guest.',
            403, false);
    }
}
