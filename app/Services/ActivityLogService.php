<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\PatientActivityLog;

class ActivityLogService
{
    public function log(?int $patientUserId, string $action, ?string $description = null): void
    {
        $request = request();

        PatientActivityLog::create([
            'patient_user_id' => $patientUserId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request?->header('x-forwarded-for') ?: $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
