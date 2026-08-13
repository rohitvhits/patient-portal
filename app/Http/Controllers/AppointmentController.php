<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentDocument;
use App\Services\ActivityLogService;
use App\Services\ErpApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    public function __construct(
        protected ErpApiService $erpApi,
        protected ActivityLogService $activityLog,
    ) {
    }

    public function index(Request $request)
    {
        $patientUser = Auth::guard('patient')->user();

        $remote = $this->erpApi->appointments($patientUser->mobile);

        foreach ($remote as $row) {
            Appointment::updateOrCreate(
                ['patient_user_id' => $patientUser->id, 'erp_appointment_id' => $row['id']],
                [
                    'appointment_date' => $row['appointment_date'] ?? null,
                    'appointment_time' => $row['appointment_time'] ?? null,
                    'status' => $row['status'] ?? null,
                    'location_name' => $row['location_name'] ?? null,
                    'doctor_name' => $row['doctor_name'] ?? null,
                    'service_name' => $row['service_name'] ?? null,
                ]
            );
        }

        $appointments = $patientUser->appointments()
            ->orderByDesc('appointment_date')
            ->get();

        $this->activityLog->log($patientUser->id, 'appointment_list_viewed');

        return view('appointments.index', ['appointments' => $appointments]);
    }

    public function show(Request $request, Appointment $appointment)
    {
        $patientUser = Auth::guard('patient')->user();

        if (!$appointment->ownedBy($patientUser)) {
            $this->activityLog->log($patientUser->id, 'unauthorized_access', "appointment_id={$appointment->id}");
            abort(403);
        }

        $detail = $this->erpApi->appointmentDetail($patientUser->mobile, $appointment->erp_appointment_id);

        if ($detail) {
            $appointment->fill([
                'appointment_date' => $detail['appointment_date'] ?? $appointment->appointment_date,
                'appointment_time' => $detail['appointment_time'] ?? $appointment->appointment_time,
                'status' => $detail['status'] ?? $appointment->status,
                'location_name' => $detail['location_name'] ?? $appointment->location_name,
                'doctor_name' => $detail['doctor_name'] ?? $appointment->doctor_name,
                'service_name' => $detail['service_name'] ?? $appointment->service_name,
            ])->save();
        }

        $this->activityLog->log($patientUser->id, 'appointment_detail_viewed', "appointment_id={$appointment->id}");

        $remoteDocuments = $this->erpApi->documents($patientUser->mobile, $appointment->erp_appointment_id);

        foreach ($remoteDocuments as $doc) {
            AppointmentDocument::updateOrCreate(
                ['appointment_id' => $appointment->id, 'erp_document_id' => $doc['id']],
                [
                    'document_name' => $doc['document_name'] ?? null,
                    'extension' => $doc['extension'] ?? null,
                    'size_in_bytes' => $doc['size_in_bytes'] ?? null,
                ]
            );
        }

        $documents = $appointment->documents()->orderByDesc('id')->get();

        $this->activityLog->log($patientUser->id, 'document_list_viewed', "appointment_id={$appointment->id}");

        return view('appointments.show', ['appointment' => $appointment, 'documents' => $documents]);
    }
}
