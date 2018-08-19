<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsVehicle extends Model {
    use SoftDeletes;

    protected $table = 'ms_vehicle';
    protected $fillable = ['license_plate','imei_obd_number','simcard_number','year_of_vehicle','color_vehicle','brand_veicle_id','model_vehicle_id','chassis_number','machine_number','date_stnk','date_installation','speed_limit','odometer','status'];

}