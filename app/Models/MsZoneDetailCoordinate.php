<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsZoneDetailCoordinate extends Model {
    use SoftDeletes;

    protected $table = 'ms_zone_detail_coordinate';
    protected $fillable = ['zone_id','latitude','longitude','status'];

}