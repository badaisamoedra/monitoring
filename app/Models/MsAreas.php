<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsAreas extends Model {
    use SoftDeletes;

    protected $table = 'ms_areas';
    protected $fillable = ['area_name','status'];

}