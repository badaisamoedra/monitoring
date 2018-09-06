<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class RptDriverScoring extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'rpt_driver_scoring';

    protected $guarded = ['id'];

}