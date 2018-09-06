<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class BestDriver extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'best_driver';

    protected $guarded = ['id'];

}