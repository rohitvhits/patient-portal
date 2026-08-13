<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'patient_user_id',
        'erp_patient_id',
        'agency_id',
        'first_name',
        'last_name',
        'dob',
    ];

    public function patientUser()
    {
        return $this->belongsTo(PatientUser::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
