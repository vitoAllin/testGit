<?php

namespace App\Http\Model;

use App\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laratrust\Traits\LaratrustUserTrait;
use Auth;


class User extends Authenticatable
{
    use LaratrustUserTrait;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table='user';
    protected $primaryKey='user_id';
    public $timestamps=false;
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    //获取当前登陆的user用户
    public static function activeUser()
    {
        $user = Auth::user();
        return $user;
    }

}
