<?php

namespace App\Http\Middleware;

use Closure;

class CheckForActiveGroup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->route('group')->is_active || !$request->route('group')->hotels()->whereHas('rooms')->exists()) {
            abort(404);
        }

        return $next($request);
    }
}
