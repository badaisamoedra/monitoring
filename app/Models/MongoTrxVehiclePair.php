<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoTrxVehiclePair extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'mongo_trx_vehicle_pair';

    // protected $fillable = [
        
    // ];

}