<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;

class Applicationuser extends Model
{
    protected $table='user';
    protected $primaryKey='user_id';
    protected  $isWechat = 'iswechat';
    public $timestamps=false;
    protected $guarded=[];
}
