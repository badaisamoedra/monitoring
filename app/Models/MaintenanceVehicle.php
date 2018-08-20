<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceVehicle extends Model {
    use SoftDeletes;

    protected $table = 'maintenance_vehicle';
    protected $fillable = ['maintenance_vehicle_code','imei_obd_number_old','imei_obd_number_new','simcard_number_old','simcard_number_new','start_date_maintenance','end_date_maintenance','status'];
}