<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['patient_user_id', 'appointment_document_id', 'file_name', 'ip_address', 'created_at'];
}
