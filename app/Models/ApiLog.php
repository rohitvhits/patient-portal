<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['endpoint', 'method', 'response_status', 'created_at'];
}
