<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsZone extends Model {
    use SoftDeletes;

    protected $table = 'ms_zone';
}