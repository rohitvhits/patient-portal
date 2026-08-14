<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\ApiLog;

/**
 * Thin client for the ERP (nybesterp) Patient Portal APIs
 * (App\Http\Controllers\API\Portal\PatientPortalController over there).
 * Every call is authenticated with a static bearer token and logged locally.
 */
class ErpApiService
{
    protected function client()
    {
        return Http::baseUrl(config('services.erp.base_url'))
            ->withHeaders(['Authorization' => config('services.erp.token')])
            ->timeout(15)
            ->acceptJson();
    }

    protected function log(string $method, string $endpoint, ?int $status): void
    {
        ApiLog::create([
            'endpoint' => $endpoint,
            'method' => $method,
            'response_status' => $status,
            'created_at' => now(),
        ]);
    }

    public function requestOtp(string $mobile): array
    {
        $response = $this->client()->post('/request-otp', ['mobile' => $mobile]);
        $this->log('POST', '/request-otp', $response->status());

        return ['status' => $response->status(), 'body' => $response->json() ?? []];
    }

    public function verifyOtp(string $mobile, string $otp): array
    {
        $response = $this->client()->post('/verify-otp', ['mobile' => $mobile, 'otp' => $otp]);
        $this->log('POST', '/verify-otp', $response->status());

        return ['status' => $response->status(), 'body' => $response->json() ?? []];
    }

    /**
     * Null means the ERP call itself failed (so callers should leave any local
     * cache alone); an array — even empty — is an authoritative, current
     * snapshot the caller can safely sync/delete stale local rows against.
     */
    public function appointments(string $mobile): ?array
    {
        $response = $this->client()->get('/appointments', ['mobile' => $mobile]);
        $this->log('GET', '/appointments', $response->status());

        if (!$response->successful()) {
            return null;
        }

        // A 200 with a missing/malformed "data" key is treated the same as a
        // failed call (null) — only a genuine array (including []) counts as an
        // authoritative snapshot callers may delete stale local rows against.
        $data = $response->json('data');

        return is_array($data) ? $data : null;
    }

    public function appointmentDetail(string $mobile, int $erpAppointmentId): ?array
    {
        $response = $this->client()->get("/appointments/{$erpAppointmentId}", ['mobile' => $mobile]);
        $this->log('GET', "/appointments/{$erpAppointmentId}", $response->status());

        return $response->successful() ? $response->json('data') : null;
    }

    /**
     * Null means the ERP call itself failed (so callers should leave any local
     * cache alone); an array — even empty — is an authoritative, current
     * snapshot the caller can safely sync/delete stale local rows against.
     */
    public function documents(string $mobile, int $erpAppointmentId): ?array
    {
        $response = $this->client()->get('/documents', ['mobile' => $mobile, 'appointment_id' => $erpAppointmentId]);
        $this->log('GET', '/documents', $response->status());

        if (!$response->successful()) {
            return null;
        }

        // Same rule as appointments() above — a missing/malformed "data" key is
        // treated as a failed call, not as "zero documents".
        $data = $response->json('data');

        return is_array($data) ? $data : null;
    }

    /**
     * Streams the document bytes back from the ERP. Returns null on failure
     * so the controller can 404 instead of forwarding a broken response.
     */
    public function downloadDocument(string $mobile, int $erpDocumentId): ?array
    {
        $response = $this->client()->get("/documents/{$erpDocumentId}/download", ['mobile' => $mobile]);
        $this->log('GET', "/documents/{$erpDocumentId}/download", $response->status());

        if (!$response->successful()) {
            return null;
        }

        return [
            'body' => $response->body(),
            'content_type' => $response->header('Content-Type') ?: 'application/octet-stream',
            'content_disposition' => $response->header('Content-Disposition'),
        ];
    }
}
