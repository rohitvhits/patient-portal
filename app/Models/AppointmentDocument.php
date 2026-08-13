<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentDocument extends Model
{
    protected $fillable = [
        'appointment_id',
        'erp_document_id',
        'document_name',
        'extension',
        'size_in_bytes',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function ownedBy(PatientUser $patientUser): bool
    {
        return $this->appointment && $this->appointment->ownedBy($patientUser);
    }
}
