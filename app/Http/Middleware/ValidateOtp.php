<?php

namespace App\Http\Middleware;

use App\Notifications\OtpNotification;
use Closure;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class ValidateOtp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user->otp_verified_at && app()->environment('Production')) {
            $google2fa = new Google2FA();
            $otp = $google2fa->getCurrentOtp($user->two_factor_secret);

            $user->notify(new OtpNotification($otp));

            return redirect()->route('2fa');
        }

        return $next($request);
    }
}
