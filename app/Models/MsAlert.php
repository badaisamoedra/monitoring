<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsAlert extends Model {
    use SoftDeletes;

    protected $table = 'ms_alert';
    protected $fillable = ['alert_code','alert_name','notification_code'];

    public function notification() {
        return $this->belongsTo('App\Models\MsNotification', 'notification_code', 'notification_code');
    }

}