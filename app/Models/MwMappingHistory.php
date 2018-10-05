<?php
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;


class MwMappingHistory extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'mw_mapping_history';

    protected $guarded = ['id'];

}