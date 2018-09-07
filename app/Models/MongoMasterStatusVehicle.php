<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoMasterStatusVehicle extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'master_status_vehicle';

    protected $guarded = ['id'];

}