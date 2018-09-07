<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsAlert extends Model {
    use SoftDeletes;

    protected $table = 'ms_alert';
    protected $fillable = ['alert_code','alert_name','notification_code','provision_alert_name','provision_alert_code','score','status_alert_priority_code'];

    public function notification() {
        return $this->hasMany('App\Models\MsNotification', 'notification_code', 'notification_code');
    }

    public function alertPriority() {
        return $this->hasMany('App\Models\MsStatusAlertPriority', 'alert_priority_code', 'status_alert_priority_code');
    }

}