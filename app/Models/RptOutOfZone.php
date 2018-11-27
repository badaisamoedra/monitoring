<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class RptOutOfZone extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'rpt_out_of_zone';

    protected $guarded = ['id'];
    protected $dates   = ['device_time', 'server_time', 'created_at', 'updated_at'];

}