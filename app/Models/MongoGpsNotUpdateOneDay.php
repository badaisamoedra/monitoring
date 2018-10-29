<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoGpsNotUpdateOneDay extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'gps_not_update_one_day';

    protected $guarded = ['id'];
    protected $dates   = ['created_at', 'updated_at'];

}