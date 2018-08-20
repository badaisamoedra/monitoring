<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionVehiclePair extends Model {
    use SoftDeletes;

    protected $table = 'transaction_vehicle_pair';
    protected $fillable = ['transaction_vehicle_pair_code','vehicle_id','driver_id','start_date_pair','end_date_pair','status'];

}