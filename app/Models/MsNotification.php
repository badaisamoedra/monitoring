<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsNotification extends Model {
    use SoftDeletes;

    protected $table = 'ms_notification';
    protected $fillable = ['notification_code','notification_name'];

    public function alert()
    {
        return $this->hasMany('App\Models\MsAlert','notification_code','notification_code');
    }
}