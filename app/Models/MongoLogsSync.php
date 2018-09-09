<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoLogsSync extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'logs_sync';

    protected $guarded = ['id'];

}