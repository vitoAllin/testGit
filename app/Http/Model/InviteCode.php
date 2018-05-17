<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;

class InviteCode extends Model
{
    protected $table='invitecode';
    protected $primaryKey='invite_id';
    public $timestamps=false;
    protected $guarded=[];
}
