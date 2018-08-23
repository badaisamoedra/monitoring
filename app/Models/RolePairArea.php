<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RolePairArea extends Model {
    use SoftDeletes;

    protected $table = 'role_pair_area';
    protected $fillable = ['role_area_code','role_code','area_code'];

    public function role() {
        return $this->belongsTo('App\Models\MsRole', 'role_code');
    }

    public function area() {
        return $this->belongsTo('App\Models\MsAreas', 'area_code');
    }

}