<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsZone extends Model {
    use SoftDeletes;

    protected $table = 'ms_zone';
    protected $fillable = ['zone_code','type_zone','zone_name','status','area_code'];

    public function zone_detail()
    {
        return $this->hasMany('App\Models\MsZoneDetailCoordinate','zone_code','zone_code');
    }

}