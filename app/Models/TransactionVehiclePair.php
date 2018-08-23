<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionVehiclePair extends Model {
    use SoftDeletes;

    protected $table = 'transaction_vehicle_pair';
    protected $fillable = ['transaction_vehicle_pair_code','vehicle_code','driver_code','start_date_pair','end_date_pair','status'];

    public function vehicle() {
        return $this->belongsTo('App\Models\MsVehicle', 'vehicle_code', 'vehicle_code');
    }

    public function driver() {
        return $this->belongsTo('App\Models\MsDriver', 'driver_code', 'driver_code');
    }

}