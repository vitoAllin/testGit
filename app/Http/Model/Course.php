<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table='course';
    protected $primaryKey='cou_id';
    public $timestamps=false;
    protected $guarded=[];
}
