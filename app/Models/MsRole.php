<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsRole extends Model {
    use SoftDeletes;

    protected $table = 'ms_roles';
    protected $fillable = ['role_code','role_name', 'status'];

}