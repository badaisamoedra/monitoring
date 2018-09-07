<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoMasterStatusEvent extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'master_status_event';

    protected $guarded = ['id'];

}