<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsBrandVehicle extends Model {
    use SoftDeletes;

    protected $table = 'ms_brand_vehicle';
    protected $fillable = ['brand_vehicle_code','brand_vehicle_name','status'];

    public function vehicle() {
        return $this->hasMany('App\Models\MsVehicle', 'brand_vehicle_code','brand_vehicle_code');
    }

}