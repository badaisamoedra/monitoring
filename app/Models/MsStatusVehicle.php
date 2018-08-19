<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsStatusVehicle extends Model {
    use SoftDeletes;

    protected $table = 'ms_status_vehicle';
    protected $fillable = ['status_name'];

}