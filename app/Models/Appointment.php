<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'patient_user_id',
        'erp_appointment_id',
        'appointment_date',
        'appointment_time',
        'status',
        'location_name',
        'doctor_name',
        'service_name',
        'agency_name',
    ];

    public function patientUser()
    {
        return $this->belongsTo(PatientUser::class);
    }

    public function documents()
    {
        return $this->hasMany(AppointmentDocument::class);
    }

    /**
     * True only when this appointment row belongs to the given patient user —
     * the ownership check every controller must run before showing/using it.
     */
    public function ownedBy(PatientUser $patientUser): bool
    {
        return (int) $this->patient_user_id === (int) $patientUser->id;
    }
}
