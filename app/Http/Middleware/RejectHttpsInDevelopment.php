<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RejectHttpsInDevelopment
{
    public function handle(Request $request, Closure $next)
    {
        // Reject HTTPS requests in development (they cause "Unsupported SSL request" errors)
        if (app()->environment('local', 'testing') && $request->secure()) {
            abort(400, 'HTTPS not supported in development');
        }

        return $next($request);
    }
}
