<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;

class AppManage extends Model
{
    protected $table='app';
    protected $primaryKey='app_id';
    public $timestamps=false;
    protected $guarded=[];
}
