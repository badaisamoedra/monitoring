<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoMasterAddress extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'master_address';

    protected $guarded = ['id'];

}