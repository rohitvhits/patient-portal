<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientUser;
use App\Services\ActivityLogService;
use App\Services\ErpApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        protected ErpApiService $erpApi,
        protected ActivityLogService $activityLog,
    ) {
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function requestOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'min:7', 'max:20'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $mobile = $request->input('mobile');
        $this->erpApi->requestOtp($mobile);

        $request->session()->put('otp_mobile', $mobile);

        return redirect()->route('login.otp')->with('status', 'If this mobile number is registered, a verification code has been sent.');
    }

    public function showOtp(Request $request)
    {
        if (!$request->session()->has('otp_mobile')) {
            return redirect()->route('login');
        }

        return view('auth.otp', ['mobile' => $request->session()->get('otp_mobile')]);
    }

    public function verifyOtp(Request $request)
    {
        $mobile = $request->session()->get('otp_mobile');

        if (empty($mobile)) {
            return redirect()->route('login');
        }

        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $result = $this->erpApi->verifyOtp($mobile, $request->input('otp'));
        $body = $result['body'];

        if ($result['status'] !== 200 || empty($body['status'])) {
            $this->activityLog->log(null, 'login_failed', "mobile={$mobile}");

            return back()->withErrors(['otp' => $body['message'] ?? 'Invalid or expired verification code.']);
        }

        $patientUser = PatientUser::firstOrCreate(
            ['mobile' => $body['data']['mobile']],
            ['status' => 'active']
        );

        if (!$patientUser->isActive()) {
            $this->activityLog->log($patientUser->id, 'login_failed', 'account blocked');

            return back()->withErrors(['otp' => 'This account has been blocked. Please contact support.']);
        }

        foreach ($body['data']['patients'] ?? [] as $erpPatient) {
            Patient::updateOrCreate(
                ['patient_user_id' => $patientUser->id, 'erp_patient_id' => $erpPatient['erp_patient_id']],
                [
                    'agency_id' => $erpPatient['agency_id'] ?? null,
                    'first_name' => $erpPatient['first_name'] ?? null,
                    'last_name' => $erpPatient['last_name'] ?? null,
                    'dob' => $erpPatient['dob'] ?? null,
                ]
            );
        }

        $patientUser->update(['last_login_at' => now()]);

        $request->session()->forget('otp_mobile');
        Auth::guard('patient')->login($patientUser, remember: true);
        $request->session()->regenerate();

        $this->activityLog->log($patientUser->id, 'login_success');

        return redirect()->intended(route('appointments.index'));
    }

    public function logout(Request $request)
    {
        $patientUser = Auth::guard('patient')->user();
        $this->activityLog->log($patientUser?->id, 'logout');

        Auth::guard('patient')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
