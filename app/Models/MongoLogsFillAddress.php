<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MongoLogsFillAddress extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'logs_fill_address';

    protected $guarded = ['id'];

}