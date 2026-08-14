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

    /**
     * The ERP's legacy `patient_master.dob` column carries MySQL zero-dates
     * ('0000-00-00') for patients with no DOB on file instead of NULL — not a
     * valid date, and this app's own (strict-mode) `dob` column rejects it
     * outright. Normalize to null wherever a dob is synced in from the ERP.
     */
    public static function sanitizeDob(?string $value): ?string
    {
        if (empty($value) || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        return $value;
    }
}
