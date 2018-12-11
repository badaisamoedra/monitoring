<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoLogsReport extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'logs_report';

    protected $guarded = ['id'];

}