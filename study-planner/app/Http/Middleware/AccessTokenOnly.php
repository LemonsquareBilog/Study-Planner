<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AccessTokenOnly
{
    public function handle(Request $request, Closure $next)
    {
        abort_if($request->user()->currentAccessToken()?->can('refresh') &&
            ! $request->user()->currentAccessToken()?->can('*'), 401, 'Access token required.');
        abort_unless($request->user()->is_active, 403, 'This account has been deactivated. Contact your administrator.');

        return $next($request);
    }
}
