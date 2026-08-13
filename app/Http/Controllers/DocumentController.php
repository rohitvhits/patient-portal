<?php

namespace App\Http\Controllers;

use App\Models\AppointmentDocument;
use App\Models\DownloadLog;
use App\Services\ActivityLogService;
use App\Services\ErpApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function __construct(
        protected ErpApiService $erpApi,
        protected ActivityLogService $activityLog,
    ) {
    }

    public function download(Request $request, AppointmentDocument $document)
    {
        $patientUser = Auth::guard('patient')->user();

        if (!$document->ownedBy($patientUser)) {
            $this->activityLog->log($patientUser->id, 'unauthorized_access', "document_id={$document->id}");
            abort(403);
        }

        $file = $this->erpApi->downloadDocument($patientUser->mobile, $document->erp_document_id);

        if (empty($file)) {
            abort(404, 'Document not found.');
        }

        DownloadLog::create([
            'patient_user_id' => $patientUser->id,
            'appointment_document_id' => $document->id,
            'file_name' => $document->document_name,
            'ip_address' => $request->header('x-forwarded-for') ?: $request->ip(),
            'created_at' => now(),
        ]);

        $this->activityLog->log($patientUser->id, 'document_downloaded', "document_id={$document->id}");

        $disposition = $file['content_disposition'] ?: 'attachment; filename="' . addslashes($document->document_name ?: 'document') . '"';

        return response($file['body'], 200, [
            'Content-Type' => $file['content_type'],
            'Content-Disposition' => $disposition,
        ]);
    }
}
