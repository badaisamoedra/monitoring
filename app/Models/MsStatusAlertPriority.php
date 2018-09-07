<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsStatusAlertPriority extends Model {
    use SoftDeletes;

    protected $table = 'ms_status_alert_priority';
    protected $fillable = ['alert_priority_code','alert_priority_name','alert_priority_color_hex'];

    public function alert()
    {
        return $this->hasMany('App\Models\MsAlert','alert_priority_code','alert_priority_code');
    }

}