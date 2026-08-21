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

    /**
     * The ERP call this page depends on is synchronous and can take a couple of
     * seconds — long enough that a plain browser navigation (typed URL, refresh,
     * bookmark) shows nothing but a blank tab the whole time, with no way for any
     * client-side loader to render (nothing has reached the browser yet). So this
     * route now serves two shapes on the same URL: a fast "shell" (no ERP call,
     * just the chrome + a skeleton) for a normal navigation, and — the instant that
     * shell has loaded — its own JS immediately re-requests the same URL with an
     * X-Requested-With header, which this method detects via $request->ajax() and
     * answers with the real, ERP-backed content as a JSON-wrapped HTML fragment
     * (see indexFragment()). In-app link/button clicks still get the full-page
     * overlay from partials/page-loader.blade.php on top of this, same as before.
     */
    public function index(Request $request)
    {
        $patientUser = Auth::guard('patient')->user();

        if ($request->ajax()) {
            return $this->indexFragment($request, $patientUser);
        }

        return view('appointments.index', [
            // Read from the local audit table only — no live ERP call — so the
            // greeting can render with the patient's name on the very first,
            // instant paint. It was synced from a real ERP response on an earlier
            // request and rarely goes stale (see HANDOFF.md §6).
            'patientIdentities' => $patientUser->patients()->get()->keyBy('erp_patient_id'),
            'patientUser' => $patientUser,
            'filters' => [
                'search' => trim((string) $request->query('search', '')),
                'status' => trim((string) $request->query('status', '')),
                'agency' => trim((string) $request->query('agency', '')),
            ],
        ]);
    }

    protected function indexFragment(Request $request, PatientUser $patientUser)
    {
        $remote = $this->erpApi->appointments($patientUser->mobile);
        $appointmentTelehealth = collect();
        $appointmentSchedules = collect();
        $erpUnavailable = false;

        if ($remote === null) {
            // The ERP call itself failed. nybesterp is a live, separately-changing
            // system — silently falling back to whatever is sitting in the local
            // `appointments` table would risk showing data that's since changed or
            // been deleted upstream, with no indication it's stale. So on failure
            // we show nothing (not old cached rows) and surface the failure instead.
            $erpUnavailable = true;
            $liveRows = collect();
            $patientIdentities = collect();
        } else {
            // `patients`/`appointments` are written here purely as a local audit
            // trail (and to hand out an internal id for building /appointments/{id}
            // links) — never read back for what actually gets *displayed* below.
            // Every value rendered on this page comes straight from $remote, this
            // request's live ERP response. An array (even empty) is authoritative,
            // so this also deletes any local rows the ERP no longer reports.
            $this->syncAppointments($patientUser, $remote, $appointmentTelehealth, $appointmentSchedules);

            $idMap = $patientUser->appointments()->pluck('id', 'erp_appointment_id');

            $liveRows = collect($remote)->map(function ($row) use ($idMap) {
                return (new Appointment())->forceFill([
                    'id' => $idMap[$row['id']] ?? null,
                    'erp_appointment_id' => $row['id'],
                    'appointment_date' => $row['appointment_date'] ?? null,
                    'appointment_time' => $row['appointment_time'] ?? null,
                    'status' => $row['status'] ?? null,
                    'location_name' => $row['location_name'] ?? null,
                    'doctor_name' => $row['doctor_name'] ?? null,
                    'service_name' => $row['service_name'] ?? null,
                    'agency_name' => $this->agencyNameFromRow($row),
                ]);
            })->filter(fn ($appointment) => $appointment->id !== null)->values();

            $patientIdentities = $patientUser->patients()
                ->get()
                ->keyBy('erp_patient_id');
        }

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $agency = trim((string) $request->query('agency', ''));

        // Patient identities are a small, already-loaded set (family members sharing
        // this login) — matched here in PHP, then folded into the same
        // erp_appointment_id space the live rows above filter on (an ERP appointment
        // id and its owning patient's erp_patient_id are the same patient_master row
        // id — see AppointmentController::syncPatientIdentity).
        $matchingPatientIds = $search === ''
            ? collect()
            : $patientIdentities->filter(fn ($patient) => str_contains(strtolower($patient->full_name), strtolower($search)))->keys();

        $filtered = $liveRows->filter(function ($appointment) use ($search, $status, $agency, $matchingPatientIds) {
            if ($search !== '') {
                $haystack = strtolower($appointment->service_name.' '.$appointment->agency_name.' '.$appointment->status.' '.$appointment->erp_appointment_id);
                $matchesPatient = $matchingPatientIds->contains($appointment->erp_appointment_id);

                if (!str_contains($haystack, strtolower($search)) && !$matchesPatient) {
                    return false;
                }
            }

            if ($status !== '' && $appointment->status !== $status) {
                return false;
            }

            if ($agency !== '' && $appointment->agency_name !== $agency) {
                return false;
            }

            return true;
        })->sortByDesc('appointment_date')->values();

        $statusOptions = $liveRows->pluck('status')->filter()->unique()->sort()->values();
        $agencyOptions = $liveRows->pluck('agency_name')->filter()->unique()->sort()->values();

        $perPage = 25;
        $page = (int) ($request->query('page', 1)) ?: 1;
        $appointments = new \Illuminate\Pagination\LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $this->activityLog->log($patientUser->id, 'appointment_list_viewed');

        $filters = ['search' => $search, 'status' => $status, 'agency' => $agency];

        return response()->json([
            'html' => view('appointments._results', [
                'appointments' => $appointments,
                'appointmentTelehealth' => $appointmentTelehealth,
                'appointmentSchedules' => $appointmentSchedules,
                'patientIdentities' => $patientIdentities,
                'filters' => $filters,
                'erpUnavailable' => $erpUnavailable,
            ])->render(),
            'total' => $appointments->total(),
            'statusOptions' => $statusOptions->values(),
            'agencyOptions' => $agencyOptions->values(),
        ]);
    }

    /**
     * Same shell-then-fragment split as index() above, and for the same reason —
     * appointmentDetailAndDocuments() is a live, synchronous ERP round trip. The
     * ownership check stays in the shell branch: it's a local-DB-only check (no ERP
     * call needed) and must reject a cross-patient id before any fragment fetch is
     * even offered, not just before the fragment renders.
     */
    public function show(Request $request, Appointment $appointment)
    {
        $patientUser = Auth::guard('patient')->user();

        if (!$appointment->ownedBy($patientUser)) {
            $this->activityLog->log($patientUser->id, 'unauthorized_access', "appointment_id={$appointment->id}");
            abort(403);
        }

        if ($request->ajax()) {
            return $this->showFragment($request, $patientUser, $appointment);
        }

        return view('appointments.show', [
            'appointment' => $appointment,
            'patientUser' => $patientUser,
        ]);
    }

    protected function showFragment(Request $request, PatientUser $patientUser, Appointment $appointment)
    {
        // Detail and documents are two independent ERP calls — fired concurrently
        // (one connection pool) instead of one-after-another, so this page waits on
        // the slower of the two calls rather than the sum of both.
        $pooled = $this->erpApi->appointmentDetailAndDocuments($patientUser->mobile, $appointment->erp_appointment_id);
        $detail = $pooled['detail'];
        $remoteDocuments = $pooled['documents'];
        $appointmentAgencyName = null;
        $telehealth = null;
        $schedule = null;
        $appointmentUnavailable = false;

        if ($detail) {
            $this->syncPatientIdentity($patientUser, $detail);
            $appointmentAgencyName = $this->agencyNameFromRow($detail);
            $telehealth = $this->telehealthFromRow($detail);
            $schedule = $this->scheduleFromRow($detail);

            // save() here is an audit copy only — $appointment (in memory) now holds
            // this request's live ERP values, and that in-memory object (not a fresh
            // SELECT) is what gets handed to the view below.
            $appointment->fill([
                'appointment_date' => $detail['appointment_date'] ?? $appointment->appointment_date,
                'appointment_time' => $detail['appointment_time'] ?? $appointment->appointment_time,
                'status' => $detail['status'] ?? $appointment->status,
                'location_name' => $detail['location_name'] ?? $appointment->location_name,
                'doctor_name' => $detail['doctor_name'] ?? $appointment->doctor_name,
                'service_name' => $detail['service_name'] ?? $appointment->service_name,
            ])->save();
        } else {
            // ERP call failed — we still show the last-known audit copy (there is no
            // other source of truth for this one appointment on this page), but flag
            // it so the view can tell the patient it may not be current.
            $appointmentUnavailable = true;
        }

        $this->activityLog->log($patientUser->id, 'appointment_detail_viewed', "appointment_id={$appointment->id}");

        $documentsUnavailable = false;

        if ($remoteDocuments === null) {
            // ERP call failed — don't pretend the local cache is a current document
            // list; show nothing rather than possibly-stale/possibly-deleted files.
            $documentsUnavailable = true;
            $documents = collect();
        } else {
            // `appointment_documents` is written here purely as a local audit trail
            // (and to hand out an internal id for the download route) — the list
            // actually rendered below is built straight from $remoteDocuments, this
            // request's live ERP response. An array (even empty) is authoritative,
            // so any locally cached document the ERP no longer reports gets dropped.
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

            $idMap = $appointment->documents()->pluck('id', 'erp_document_id');

            $documents = collect($remoteDocuments)->map(function ($doc) use ($idMap, $appointment) {
                return (new AppointmentDocument())->forceFill([
                    'id' => $idMap[$doc['id']] ?? null,
                    'appointment_id' => $appointment->id,
                    'erp_document_id' => $doc['id'],
                    'document_name' => $doc['document_name'] ?? null,
                    'extension' => $doc['extension'] ?? null,
                    'size_in_bytes' => $doc['size_in_bytes'] ?? null,
                ]);
            })->filter(fn ($document) => $document->id !== null)->sortByDesc('id')->values();
        }

        $patientIdentity = $patientUser->patients()
            ->where('erp_patient_id', $appointment->erp_appointment_id)
            ->first();

        $this->activityLog->log($patientUser->id, 'document_list_viewed', "appointment_id={$appointment->id}");

        return response()->json([
            'html' => view('appointments._detail-content', [
                'appointment' => $appointment,
                'appointmentAgencyName' => $appointmentAgencyName,
                'telehealth' => $telehealth,
                'schedule' => $schedule,
                'documents' => $documents,
                'patientIdentity' => $patientIdentity,
                'appointmentUnavailable' => $appointmentUnavailable,
                'documentsUnavailable' => $documentsUnavailable,
            ])->render(),
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