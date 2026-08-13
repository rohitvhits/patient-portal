<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * The only kind of login account in this project. There is no password —
 * patients authenticate by mobile number + OTP verified against the ERP
 * (see App\Http\Controllers\Auth\AuthController), then get logged in
 * directly via Auth::guard('patient')->login($patientUser).
 */
class PatientUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'mobile',
        'status',
        'last_login_at',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
