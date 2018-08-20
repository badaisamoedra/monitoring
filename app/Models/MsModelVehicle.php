<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsModelVehicle extends Model {
    use SoftDeletes;

    protected $table = 'ms_model_vehicle';
    protected $fillable = ['model_vehicle_code','model_vehicle_name','brand_vehicle_code','status'];

}