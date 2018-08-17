<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceVehicle extends Model {
    use SoftDeletes;

    protected $table = 'maintenance_vehicle';
}