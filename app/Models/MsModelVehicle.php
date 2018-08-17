<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsModelVehicle extends Model {
    use SoftDeletes;

    protected $table = 'ms_model_vehicle';
}