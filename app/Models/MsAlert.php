<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsAlert extends Model {
    use SoftDeletes;

    protected $table = 'ms_alert';
    protected $fillable = ['alert_name','notif_id'];

}