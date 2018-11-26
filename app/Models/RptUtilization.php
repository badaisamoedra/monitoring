<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class RptUtilization extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'rpt_utilization';

    protected $guarded = ['id'];
    protected $dates   = ['device_time', 'server_time', 'created_at', 'updated_at'];

}