<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsDriver extends Model {
    use SoftDeletes;

    protected $table = 'ms_driver';
    protected $fillable = ['name','spk_number','area_id','status'];

}