<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsRole extends Model {
    use SoftDeletes;

    protected $table = 'MsRoles';
    protected $primaryKey = 'Id';


    protected $guarded = ['Id'];
    protected $dates   = ['deleted_at'];
    
    public $timestamps = false;
}