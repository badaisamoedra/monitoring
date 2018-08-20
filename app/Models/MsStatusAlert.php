<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsStatusAlert extends Model {
    use SoftDeletes;

    protected $table = 'ms_status_alert';
    protected $fillable = ['status_alert_code','status_alert_name','status'];

}