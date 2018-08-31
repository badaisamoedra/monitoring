<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsVehicle extends Model {
    use SoftDeletes;

    protected $table = 'ms_vehicle';
    protected $fillable = [
            'vehicle_code',
            'license_plate',
            'imei_obd_number',
            'simcard_number',
            'year_of_vehicle',
            'color_vehicle',
            'brand_vehicle_code',
            'model_vehicle_code',
            'chassis_number',
            'machine_number',
            'date_stnk',
            'date_installation',
            'speed_limit',
            'odometer',
            'area_code',
            'status'
        ];

    public function brand()
    {
        return $this->hasOne('App\Models\MsBrandVehicle','brand_vehicle_code','brand_vehicle_code');
    }

    public function model()
    {
        return $this->hasOne('App\Models\MsModelVehicle','model_vehicle_code','model_vehicle_code');
    }

}