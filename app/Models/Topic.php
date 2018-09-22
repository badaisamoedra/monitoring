<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class Topic extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'topic';

    protected $guarded = ['id'];

}