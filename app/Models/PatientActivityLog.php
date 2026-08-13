<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientActivityLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['patient_user_id', 'action', 'description', 'ip_address', 'user_agent', 'created_at'];

    public function patientUser()
    {
        return $this->belongsTo(PatientUser::class);
    }
}
