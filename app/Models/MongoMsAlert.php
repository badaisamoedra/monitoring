<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoMsAlert extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'mongo_ms_alert';

    // protected $fillable = [
        
    // ];

}