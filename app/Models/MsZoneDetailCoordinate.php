<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsZoneDetailCoordinate extends Model {
    use SoftDeletes;

    protected $table = 'ms_zone_detail_coordinate';
    protected $fillable = ['zone_detail_coordinate_code','zone_code','latitude','longitude','status'];

    public function zone() {
        return $this->belongsTo('App\Models\MsZone', 'zone_code');
    }
}