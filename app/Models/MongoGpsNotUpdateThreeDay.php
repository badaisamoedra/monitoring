<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoGpsNotUpdateThreeDay extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'gps_not_update_three_day';

    protected $guarded = ['id'];
    protected $dates   = ['device_time', 'server_time', 'last_update', 'date_installation', 'created_at', 'updated_at'];

}