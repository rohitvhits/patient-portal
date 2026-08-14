<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Logs a patient out after 15 minutes with no request on an authenticated
 * route. Deliberately an explicit check against a session timestamp rather
 * than relying on SESSION_LIFETIME/cookie expiry: login sets a remember-me
 * cookie (see AuthController::verifyOtp), which would otherwise silently
 * re-authenticate the patient once the session itself lapsed. Calling
 * logout() here clears that remember-me cookie/token too, so this is a real
 * logout rather than a lapsed session the recaller cookie papers over.
 */
class PatientIdleTimeout
{
    protected const TIMEOUT_MINUTES = 15;

    public function __construct(protected ActivityLogService $activityLog)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $lastActivity = $request->session()->get('patient_last_activity');

        if ($lastActivity && now()->diffInMinutes($lastActivity) >= self::TIMEOUT_MINUTES) {
            $patientUser = Auth::guard('patient')->user();
            $this->activityLog->log($patientUser?->id, 'session_timeout');

            Auth::guard('patient')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'You were logged out due to inactivity. Please sign in again.');
        }

        $request->session()->put('patient_last_activity', now());

        return $next($request);
    }
}
