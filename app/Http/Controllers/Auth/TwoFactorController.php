<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Notifications\OtpNotification;
use Exception;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.2fa');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|string',
        ]);

        $user = auth()->user();
        $google2fa = new Google2FA();
        $window = 10;

        if (!$google2fa->verifyKey($user->two_factor_secret, $request->otp, $window)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP is invalid.'
                ], 200);
            } else {
                throw ValidationException::withMessages([
                    'otp' => [__('The otp is invalid.')],
                ]);
            }
        }

        $user->otp_verified_at = now();
        $user->save();
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully.'
            ], 200);
        } else {
            return redirect()->intended(config('app.dashboard_url'));
        }
    }

    public function send(Request $request) {
        try {
            $google2fa = new Google2FA();

            $user = auth()->user();
            $user->two_factor_secret = $google2fa->generateSecretKey();
            $user->save();

            $otp = $google2fa->getCurrentOtp($user->two_factor_secret);
            $user->notify(new OtpNotification($otp));

            AuthLog::create([
                'ip' => $request->ip(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
               'success' => false,
               'message' => 'Failed to send OTP.'
            ], 500);
        }
    }

    public function resend() {
        $google2fa = new Google2FA();

        $user = auth()->user();
        $user->two_factor_secret = $google2fa->generateSecretKey();
        $user->save();

        $otp = $google2fa->getCurrentOtp($user->two_factor_secret);
        $user->notify(new OtpNotification($otp));

        return redirect()->route('2fa');
    }

    public function cancel(Request $request)
    {
        auth()->logout();

        return redirect()->route('login');
    }
}
