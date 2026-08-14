<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentDocument;
use App\Models\Patient;
use App\Models\PatientUser;
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
        $appointmentTelehealth = collect();
        $appointmentSchedules = collect();

        // $remote is null only when the ERP call itself failed — in that case we
        // leave the local cache untouched (fail open) rather than risk wiping it
        // out or showing an empty list. When $remote is an array (even empty) it's
        // an authoritative snapshot, so syncAppointments() also deletes any local
        // rows the ERP no longer reports (e.g. appointments deleted upstream) —
        // previously those stuck around forever since we only ever upserted.
        if ($remote !== null) {
            $this->syncAppointments($patientUser, $remote, $appointmentTelehealth, $appointmentSchedules);
        }

        $patientIdentities = $patientUser->patients()
            ->get()
            ->keyBy('erp_patient_id');

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $agency = trim((string) $request->query('agency', ''));

        // Patient identities are a small, already-loaded set (family members sharing
        // this login) — matched here in PHP rather than via a SQL join, then folded
        // into the same erp_appointment_id space the appointments table filters on
        // (an ERP appointment id and its owning patient's erp_patient_id are the same
        // patient_master row id — see AppointmentController::syncPatientIdentity).
        $matchingPatientIds = $search === ''
            ? collect()
            : $patientIdentities->filter(fn ($patient) => str_contains(strtolower($patient->full_name), strtolower($search)))->keys();

        $query = $patientUser->appointments();

        if ($search !== '') {
            $query->where(function ($q) use ($search, $matchingPatientIds) {
                $like = '%'.$search.'%';
                $q->where('service_name', 'like', $like)
                    ->orWhere('agency_name', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhere('erp_appointment_id', 'like', $like);

                if ($matchingPatientIds->isNotEmpty()) {
                    $q->orWhereIn('erp_appointment_id', $matchingPatientIds);
                }
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($agency !== '') {
            $query->where('agency_name', $agency);
        }

        $appointments = $query->orderByDesc('appointment_date')
            ->paginate(25)
            ->withQueryString();

        $statusOptions = $patientUser->appointments()->whereNotNull('status')->distinct()->orderBy('status')->pluck('status');
        $agencyOptions = $patientUser->appointments()->whereNotNull('agency_name')->distinct()->orderBy('agency_name')->pluck('agency_name');

        $this->activityLog->log($patientUser->id, 'appointment_list_viewed');

        return view('appointments.index', [
            'appointments' => $appointments,
            'appointmentTelehealth' => $appointmentTelehealth,
            'appointmentSchedules' => $appointmentSchedules,
            'patientIdentities' => $patientIdentities,
            'patientUser' => $patientUser,
            'statusOptions' => $statusOptions,
            'agencyOptions' => $agencyOptions,
            'filters' => ['search' => $search, 'status' => $status, 'agency' => $agency],
        ]);
    }

    public function show(Request $request, Appointment $appointment)
    {
        $patientUser = Auth::guard('patient')->user();

        if (!$appointment->ownedBy($patientUser)) {
            $this->activityLog->log($patientUser->id, 'unauthorized_access', "appointment_id={$appointment->id}");
            abort(403);
        }

        $detail = $this->erpApi->appointmentDetail($patientUser->mobile, $appointment->erp_appointment_id);
        $appointmentAgencyName = null;
        $telehealth = null;
        $schedule = null;

        if ($detail) {
            $this->syncPatientIdentity($patientUser, $detail);
            $appointmentAgencyName = $this->agencyNameFromRow($detail);
            $telehealth = $this->telehealthFromRow($detail);
            $schedule = $this->scheduleFromRow($detail);

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

        // null means the ERP call failed — leave the local cache as-is (fail open).
        // An array (even empty) is authoritative, so beyond upserting we also drop
        // any locally cached document the ERP no longer reports for this
        // appointment (deleted upstream) instead of leaving it around forever.
        if ($remoteDocuments !== null) {
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

            $remoteDocumentIds = array_column($remoteDocuments, 'id');
            $appointment->documents()
                ->whereNotIn('erp_document_id', $remoteDocumentIds)
                ->delete();
        }

        $documents = $appointment->documents()->orderByDesc('id')->get();
        $patientIdentity = $patientUser->patients()
            ->where('erp_patient_id', $appointment->erp_appointment_id)
            ->first();

        $this->activityLog->log($patientUser->id, 'document_list_viewed', "appointment_id={$appointment->id}");

        return view('appointments.show', [
            'appointment' => $appointment,
            'appointmentAgencyName' => $appointmentAgencyName,
            'telehealth' => $telehealth,
            'schedule' => $schedule,
            'documents' => $documents,
            'patientIdentity' => $patientIdentity,
            'patientUser' => $patientUser,
        ]);
    }

    /**
     * Persists every ERP row for this patient in two bulk queries (one upsert
     * for `patients`, one for `appointments`) instead of two Eloquent queries
     * per row. With a per-row updateOrCreate loop, a patient with hundreds of
     * appointments turned every single page load into hundreds of round
     * trips — slow enough to time out. Also fills the telehealth/schedule
     * lookup collections (never persisted — see telehealthFromRow/scheduleFromRow)
     * while walking the same rows.
     */
    protected function syncAppointments(PatientUser $patientUser, array $remote, \Illuminate\Support\Collection $appointmentTelehealth, \Illuminate\Support\Collection $appointmentSchedules): void
    {
        $now = now();
        $patientRows = [];
        $appointmentRows = [];

        foreach ($remote as $row) {
            $appointmentTelehealth->put($row['id'], $this->telehealthFromRow($row));
            $appointmentSchedules->put($row['id'], $this->scheduleFromRow($row));

            $erpPatientId = $row['patient_id'] ?? $row['erp_patient_id'] ?? $row['id'] ?? null;
            if (!empty($erpPatientId)) {
                // Keyed by erp_patient_id so a repeated id within the same ERP
                // response overwrites rather than producing duplicate rows in
                // the same upsert batch.
                $patientRows[$erpPatientId] = [
                    'patient_user_id' => $patientUser->id,
                    'erp_patient_id' => $erpPatientId,
                    'agency_id' => $row['agency_id'] ?? null,
                    'first_name' => $row['first_name'] ?? null,
                    'last_name' => $row['last_name'] ?? null,
                    'dob' => Patient::sanitizeDob($row['dob'] ?? null),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $appointmentRows[$row['id']] = [
                'patient_user_id' => $patientUser->id,
                'erp_appointment_id' => $row['id'],
                'appointment_date' => $row['appointment_date'] ?? null,
                'appointment_time' => $row['appointment_time'] ?? null,
                'status' => $row['status'] ?? null,
                'location_name' => $row['location_name'] ?? null,
                'doctor_name' => $row['doctor_name'] ?? null,
                'service_name' => $row['service_name'] ?? null,
                'agency_name' => $this->agencyNameFromRow($row),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($patientRows)) {
            Patient::upsert(
                array_values($patientRows),
                ['patient_user_id', 'erp_patient_id'],
                ['agency_id', 'first_name', 'last_name', 'dob', 'updated_at']
            );
        }

        if (!empty($appointmentRows)) {
            Appointment::upsert(
                array_values($appointmentRows),
                ['patient_user_id', 'erp_appointment_id'],
                ['appointment_date', 'appointment_time', 'status', 'location_name', 'doctor_name', 'service_name', 'agency_name', 'updated_at']
            );
        }

        // $remote is the full, current list for this patient (never a partial or
        // failed fetch — see index()), so anything cached locally that isn't in it
        // anymore was deleted/cancelled upstream in the ERP. Drop it here instead
        // of leaving stale rows around forever. Cascades to appointment_documents.
        $patientUser->appointments()
            ->whereNotIn('erp_appointment_id', array_keys($appointmentRows))
            ->delete();
    }

    protected function syncPatientIdentity(PatientUser $patientUser, array $row): void
    {
        $erpPatientId = $row['patient_id'] ?? $row['erp_patient_id'] ?? $row['id'] ?? null;

        if (empty($erpPatientId)) {
            return;
        }

        $data = [];
        foreach (['agency_id', 'first_name', 'last_name', 'dob'] as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $data[$field] = $field === 'dob' ? Patient::sanitizeDob($row[$field]) : $row[$field];
            }
        }

        if (empty($data)) {
            return;
        }

        Patient::updateOrCreate(
            ['patient_user_id' => $patientUser->id, 'erp_patient_id' => $erpPatientId],
            $data
        );
    }

    protected function agencyNameFromRow(array $row): ?string
    {
        if (!empty($row['agency_name'])) {
            return $row['agency_name'];
        }

        return !empty($row['agency_id']) ? 'Agency #'.$row['agency_id'] : null;
    }

    /**
     * The ERP can carry a Telehealth appointment alongside (or instead of) the regular
     * scheduled one on the same row — mirrors the admin's "Schedule Appointment" /
     * "Telehealth Appointment" split. Pulled straight from the API response and handed to
     * the view as-is; nothing here is persisted locally.
     */
    protected function telehealthFromRow(array $row): ?array
    {
        if (empty($row['telehealth_date'])) {
            return null;
        }

        return [
            'date' => $row['telehealth_date'],
            'time' => $row['telehealth_time'] ?? null,
            'nurse' => $row['telehealth_nurse'] ?? null,
        ];
    }

    /**
     * The Schedule Appointment's precise time range and the location's street address —
     * both shown on the admin's appointment list but not part of the plain appointment_date
     * column, so (like telehealth above) pulled from the API response only, never persisted.
     */
    protected function scheduleFromRow(array $row): ?array
    {
        $timeRange = $row['schedule_time_range'] ?? null;
        $address = $row['location_address'] ?? null;

        if (empty($timeRange) && empty($address)) {
            return null;
        }

        return [
            'timeRange' => $timeRange,
            'address' => $address,
        ];
    }
}