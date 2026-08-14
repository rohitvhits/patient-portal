# Patient Portal — Project Handoff

**Read this whole file before touching anything.** It captures everything built so far, the real (sometimes surprising) shape of the ERP data, and exactly what's left. Written for a fresh AI/developer with zero prior context on this thread.

## 1. What this project is

`nybesterp` (Laravel 9, `d:\xampp\htdocs\nybesterp`) is the main ERP — staff/agency users manage patients and appointments there. This repo, `patient-portal` (Laravel 12, `D:\xampp\htdocs\patient-portal`), is a **brand-new, separate project** with its **own database** (`patient_portal`) that lets **only patients** log in — via mobile number + OTP (no password) — to view their appointments and download their own documents.

Data is never duplicated by hand: this portal calls secure token-authenticated APIs on `nybesterp` at request time, matching the logged-in patient by phone number, and caches what it gets locally.

Source requirement doc: `d:\xampp\htdocs\nybesterp\docs\Patient_Portal_Simple_Requirement_Document_Updated.doc` (it's actually HTML, just saved with a `.doc` extension — open with a text/HTML reader, not a binary Word parser).

A Gujarati summary of the original build also exists at `d:\xampp\htdocs\nybesterp\docs\Patient_Portal_Implementation_Summary_Gujarati.doc` (same HTML-as-.doc format), but it predates the `patient_master` architecture correction in §3 below — this file supersedes it for technical accuracy.

## 2. The two-project architecture

```
Patient's browser
      │
      ▼
patient-portal (Laravel 12, its own MySQL DB "patient_portal")
      │  AuthController / AppointmentController / DocumentController
      │  → App\Services\ErpApiService (Laravel Http client, bearer token)
      ▼
nybesterp API:  /api/portal/*  (Laravel 9, MySQL DB "nybest-stagging")
      │  App\Http\Controllers\API\Portal\PatientPortalController
      │  → patient_master, document_patient tables (existing, untouched)
```

- **nybesterp is the source of truth.** patient-portal only *caches* what the API returns, keyed by the patient's own `patient_user_id`, purely so pages render fast and there's a local audit trail.
- **Auth token**: a single static bearer token, already generated and live:
  `lU7jkL4Z6jTprXcNYtP2fFB5klJnfYOC9yttg8yVCXd9NxK6IP`
  Stored in `patient-portal/.env` as `ERP_API_TOKEN`, checked against `nybesterp`'s `patient_portal_tokens` table by `App\Http\Middleware\CheckPatientPortalToken`. Mint more with `php artisan patient-portal:generate-token "name"` inside `nybesterp`.
- **CORS**: already wide open on the nybesterp side (`App\Http\Middleware\Cors`, global), no extra config needed for cross-origin calls.

## 3. ⚠️ The most important thing we learned (read this before changing anything appointment-related)

**`nybesterp` does NOT use a normalized `appointment` table for the data patients/staff actually work with.** There is an `appointment` table, but in the live `nybest-stagging` DB it has essentially no real rows (we found only 6, all our own test inserts).

**The real "appointment" data lives directly on `patient_master` rows.** Each `patient_master` row already carries its own:
- `appointment_date` (datetime — **date and time in one column**, there is no separate `appointment_time` column)
- `status`, `doctor_id`, `location_id`, `service_id` (a comma-separated list of `master_table` ids, e.g. `"27,30"`)

Proof: the admin UI's "Appointment" menu (`routes/web.php` → `Route::get('appointment', [PatientController::class, 'index'])`) is literally `PatientController::index`, i.e. a `patient_master` listing — **not** a separate appointments controller. So **one matched `patient_master` row === one "appointment"** in this system's actual data model. `App\Http\Controllers\API\Portal\PatientPortalController` on the ERP side is already written this way (see `formatAppointment()` — it Carbon-parses the single `appointment_date` datetime into separate date/time strings for the API response, and resolves `doctor_id`/`service_id` into names via `App\Model\Doctor` and `App\Master` (`master_table`, `master_type_fk = 11`)). **Do not "fix" this back to using the `Appointment` model** — that was the original (wrong) design, corrected mid-project.

Two more real-data quirks worth knowing:
- Phone numbers in `patient_master` are stored inconsistently formatted (some with dashes/parens, some blank). Matching uses `App\Helpers\Common::normalizePhoneNumberdate()` (strips to digits, drops a leading US `1`) then a `LIKE '%digits'` suffix match on `mobile`, `phone`, and `emergency_phone` — see `PatientService::getPatientsByNormalizedPhone()`. **A single phone number can legitimately match multiple `patient_master` rows** (e.g. re-referrals, shared family numbers) — the whole API is written to handle a *set* of matched patient rows, not just one.
- `document_patient.internal_use` (0 or 1) exists in the schema and is used elsewhere in the codebase (`DocumentPatientService::getAllDocumentByPatientIdApiSide`) to hide staff-only docs from external consumers. **We initially filtered `internal_use = 0` in the portal API, but the user explicitly asked to show ALL documents regardless of this flag** (see `documents()` / `downloadDocument()` in `PatientPortalController` — there's no `internal_use` filter anymore, on purpose). If a future request wants that filter back, it's a one-line `->where('internal_use', 0)` re-add in both methods.

## 4. `nybesterp` side — everything added (new files only, plus 3 tiny additive edits)

Nothing pre-existing was modified except: `routes/api.php` (new route group appended), `app/Http/Kernel.php` (2 new middleware aliases appended), `app/Services/PatientService.php` (1 new method appended, `getPatientsByNormalizedPhone`).

| File | Purpose |
|---|---|
| `database/migrations/2026_08_13_000001_create_patient_portal_tokens_table.php` | Bearer tokens for the portal client (`token`, `delete_flag`, `ip_block` CSV blocklist) |
| `database/migrations/2026_08_13_000002_create_patient_portal_otps_table.php` | OTP codes: `mobile`, `otp_hash` (bcrypt, never plaintext), `expires_at` (5 min), `attempts` (max 5), `is_used` |
| `database/migrations/2026_08_13_000003_create_patient_portal_api_logs_table.php` | One row per inbound API call (`endpoint`, `method`, `request` JSON minus `otp`, `response_status`, `ip_address`) |
| `app/Model/PatientPortalToken.php`, `PatientPortalOtp.php`, `PatientPortalApiLog.php` | Eloquent models for the above |
| `app/Http/Middleware/CheckPatientPortalToken.php` | Reads `Authorization` header, validates token + IP blocklist, aborts 401/403 JSON, else attaches token row to the request |
| `app/Http/Middleware/LogPatientPortalApiCall.php` | Writes to `patient_portal_api_logs` after every request |
| `app/Console/Commands/GeneratePatientPortalToken.php` | `php artisan patient-portal:generate-token {name}` |
| `app/Http/Controllers/API/Portal/PatientPortalController.php` | **The whole API.** See §5 for endpoints. |

### 5. The 6 API endpoints (`routes/api.php`, prefix `/api/portal`, middleware `['portal.token','portal.log']`)

All request/response shapes are defined in `PatientPortalController.php` — read it directly, it's short and the source of truth. Summary:

| Method | Route | Body/Query | What it does |
|---|---|---|---|
| POST | `/request-otp` | `mobile` | Normalizes phone, matches patients, generates+hashes a 6-digit OTP (5 min TTL), sends via `Common::sendTwillioSms()`. **Always returns the same generic "sent if registered" message** regardless of match, to prevent phone enumeration. In `APP_ENV=local` only, also logs the plaintext OTP to `storage/logs/laravel.log` as `[Patient Portal] OTP for {mobile} is {otp}` (Twilio isn't configured locally — see §7). |
| POST | `/verify-otp` | `mobile`, `otp` | Validates hash/expiry/attempts, marks OTP used, returns all matched patient identities (`erp_patient_id` = `patient_master.id`, `agency_id`, `first_name`, `last_name`, `dob`) |
| GET | `/appointments` | `mobile` | Returns every matched `patient_master` row reshaped as an "appointment" (see §3) |
| GET | `/appointments/{id}` | `mobile` | `{id}` = a `patient_master.id`; 404s (not 403) if it exists but doesn't belong to the matched set — don't leak existence |
| GET | `/documents` | `mobile`, optional `appointment_id` | `document_patient` rows for the matched patient(s), optionally narrowed to one `appointment_id` (= `patient_master.id`) |
| GET | `/documents/{id}/download` | `mobile` | Ownership-checked, then streams via `Storage::disk('s3')->download()` — same `patientdocument/` key-prefix handling as the existing `API/v1/APIController::downloadDocument` |

Every method re-derives the matched patient set from `mobile` on every single call — a client can never widen access by tampering with an id, because ids are always intersected against the freshly-matched set server-side.

## 6. `patient-portal` side — full new project

Laravel 12 default skeleton (Tailwind v4 + Vite already present) with the default `users`/`password_reset_tokens` tables and `App\Models\User` **removed** — there is no staff/admin login here, only patients.

### Auth
- `config/auth.php`: default guard is `patient`, provider `patient_users` → `App\Models\PatientUser`.
- **No password.** Login happens by calling `Auth::guard('patient')->login($patientUser)` directly after OTP success (see `AuthController::verifyOtp`) — never via `Auth::attempt()`.
- `bootstrap/app.php`: `redirectGuestsTo(route('login'))`, `redirectUsersTo(route('appointments.index'))`.

### Local DB tables (all new)
`patient_users` (mobile, status, last_login_at — the only login entity), `patients` (cached ERP identity per patient_user, keyed by `erp_patient_id`), `appointments` (cached, keyed by `erp_appointment_id` = the matched `patient_master.id` — see §3), `appointment_documents` (cached, keyed by `erp_document_id`, scoped to one local `appointment_id`), `patient_activity_logs`, `download_logs`, `api_logs` (outbound call log, this project → ERP).

### Key files
- `app/Services/ErpApiService.php` — the only place that talks HTTP to nybesterp. Reads `config('services.erp.base_url')` / `.token` (from `.env` `ERP_API_BASE_URL` / `ERP_API_TOKEN`). Logs every outbound call to local `api_logs`.
- `app/Services/ActivityLogService.php` — `log($patientUserId, $action, $description)`, called explicitly from controllers for every action the requirement doc lists (login success/failed, list/detail/document-list viewed, download, unauthorized access, logout).
- `app/Http/Controllers/Auth/AuthController.php` — `showLogin`, `requestOtp`, `showOtp`, `verifyOtp` (creates/updates local `patient_users`+`patients` from the ERP response, logs in, activity-logs), `logout`.
- `app/Http/Controllers/AppointmentController.php` — `index()` (pulls from ERP, `updateOrCreate`s the local cache, renders list), `show(Appointment $appointment)` (route-model-bound; **ownership check** via `$appointment->ownedBy($patientUser)` before anything else, 403 + `unauthorized_access` log on failure; refreshes detail + documents from ERP).
- `app/Http/Controllers/DocumentController.php` — `download(AppointmentDocument $document)` — same ownership-check pattern, then proxies the byte stream from `ErpApiService::downloadDocument()` straight to the browser (never exposes a raw S3 URL to the client).
- `app/Models/Appointment.php` / `AppointmentDocument.php` — `ownedBy(PatientUser $u)` helper method, the one true ownership check every controller must call before touching a resource.
- Views: `resources/views/layouts/{guest,app}.blade.php`, `auth/{login,otp}.blade.php` (6-box OTP input, vanilla JS, no Alpine/JS deps added on purpose), `appointments/{index,show}.blade.php`. Tailwind v4, brand palette defined in `resources/css/app.css` under `@theme` (`--color-brand-*`), NY Best logo copied to `public/img/logo.png`.

## 7. Local dev environment — how things are actually running right now

This matters because URLs are **not** the XAMPP `htdocs` defaults:

- `nybesterp` is running via `php artisan serve` on **`http://127.0.0.1:8001`** (not via XAMPP Apache's `/nybesterp/public/` path — don't assume that path works).
- `patient-portal` is running via `php artisan serve` on **`http://127.0.0.1:8000`**.
- `patient-portal/.env` → `ERP_API_BASE_URL=http://127.0.0.1:8001/api/portal` — **if the ERP's port/host ever changes, this must be updated too**, or every ERP call fails silently from the patient's point of view (the portal shows its generic "OTP sent if registered" message either way, so a broken connection here does *not* surface as an obvious error — check `nybesterp`'s `patient_portal_api_logs` table or `storage/logs/laravel.log` to confirm calls are actually arriving).
- **Twilio is not configured locally** (`TWILLIO_USERNAME`/`PASSWORD` empty in `nybesterp/.env`) — `Common::sendTwillioSms()` will silently no-op. That's why the local-only OTP-to-log-file fallback exists (§5, `request-otp`). Never rely on that fallback logic being present in production — it's gated behind `app()->environment('local')` intentionally.
- **AWS S3 is not configured locally** (`AWS_ACCESS_KEY_ID`/`SECRET` empty in `nybesterp/.env`) — `downloadDocument` will fail at the `Storage::disk('s3')->download()` call. Everything up to and including seeing the document list works locally; only the actual byte download needs real S3 creds (staging/production presumably has them).

### Known-good local test data
- Mobile `8866859940` → patient_master id 1, "Rohit Panchal" — has 1 appointment, no documents.
- Mobile `6072815373` → patient_master id 5547, "Samantha Mcclain" — has 1 appointment + 1 document ("MID").
- Mobile `7874372732` → patient_master id 87693, "Raj Panchal" — has 1 appointment + 4 documents (all were `internal_use=1`, which is exactly why the filter got removed per §3).

### To get an OTP locally
`POST http://127.0.0.1:8001/api/portal/request-otp` with header `Authorization: lU7jkL4Z6jTprXcNYtP2fFB5klJnfYOC9yttg8yVCXd9NxK6IP` and JSON body `{"mobile":"<one of the above>"}`, then read the plaintext code from the tail of `d:\xampp\htdocs\nybesterp\storage\logs\laravel.log`. (That log file has been observed to occasionally get cleared/rotated externally during this project — if the line isn't there, just re-POST, Laravel recreates the file.)

## 8. What's been verified end-to-end

Both directly via `curl` (API layer, including OTP request/verify, appointment list/detail, document list, and ownership rejection returning 404/403 for cross-patient access attempts) and via real browser sessions (cookies, CSRF, Laravel session persistence, login → OTP → appointments → detail → logout, with `patient_activity_logs` rows confirmed written for every step). All test/dummy rows created during verification have been cleaned out of both databases after each round — the DBs should currently be clean of scratch data (double-check `patient_portal_otps`, `patient_users`, `appointments` tables before treating them as pristine, in case more testing happened after this doc was written).

## 9. What's explicitly NOT done / open items

- **Production Twilio + S3 credentials** — needs whoever owns those secrets to fill in `nybesterp/.env` (or the real deployed env) before OTP SMS and document downloads work outside local dev.
- **Git**: `patient-portal` is not yet a git repository. Nothing has been committed anywhere in this work (user hasn't asked).
- **No account approval/admin gate**: any mobile number that matches an ERP patient can self-provision a portal login just by completing OTP — there's no staff-side approval step. Flagged to the user once already; they haven't asked for one, but worth surfacing again if it comes up.
- **Rate limiting** is only Laravel's route-level `throttle:5,1` / `throttle:10,1` on the login/verify routes (`patient-portal/routes/web.php`) — no additional abuse protection beyond that + the 5-attempt/5-minute OTP limits server-side.
- **No tests written** (PHPUnit/Pest) for either side — everything was verified manually via curl/browser as described in §8.
- **Deployment**: nothing here addresses how either project gets deployed to staging/production, environment separation, queue workers, etc. — pure local-dev scope so far.

## 10. If you're picking this up cold, start here

1. Read `PatientPortalController.php` on the ERP side top to bottom — it's the entire contract the portal depends on.
2. Read `AppointmentController.php` + `DocumentController.php` on the portal side — the ownership-check pattern (`->ownedBy($patientUser)` before touching anything) is the one thing to never regress on.
3. Confirm both dev servers are up on the ports in §7 before assuming anything is "broken" — most confusion during this build came from stale `.env` URLs / stale local cache rows, not actual code bugs. When in doubt, check `nybesterp`'s `patient_portal_api_logs` table to see whether the portal's calls are even reaching the ERP.
