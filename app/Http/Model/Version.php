<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    protected $table='version';
    protected $primaryKey='version_id';
    public $timestamps=false;
    protected $guarded=[];

    //获取所有的版本号按照降序排序
    public static function getAllVersion()
    {
        $allVersion = self::orderBy('version_code', 'desc')->get();
        return $allVersion;
    }

}
