<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoMasterEventRelated extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'master_alert_related';

    protected $guarded = ['id'];

}