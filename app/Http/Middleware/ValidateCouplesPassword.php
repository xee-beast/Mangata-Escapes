<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateCouplesPassword
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $group = $request->route('group');
        $verifiedAt = session("group_password_verified_{$group->id}");

        if (!$group->couples_site_password || ($verifiedAt && now()->diffInMinutes($verifiedAt) < 1440)) {
            return $next($request);
        }

        return redirect()->route('couples.password', ['group' => $group->slug]);
    }
}
