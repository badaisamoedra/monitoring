<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoMasterVehicleRelated extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'master_vehicle_related';

    protected $guarded = ['id'];

}