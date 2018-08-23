<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsAreas extends Model {
    use SoftDeletes;

    protected $table = 'ms_areas';
    protected $fillable = ['area_code','area_name','status'];

    public function role()
    {
        return $this->belongsToMany('App\Models\MsRole','role_pair_area','area_code','role_code');
    }

}