<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoLogsIntegration extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'logs_integration';

    protected $guarded = ['id'];

}