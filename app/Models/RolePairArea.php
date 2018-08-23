<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RolePairArea extends Model {
    use SoftDeletes;

    protected $table = 'role_pair_area';
    protected $fillable = ['role_area_code','role_code','area_code'];

}