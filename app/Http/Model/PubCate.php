<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class PubCate extends Model
{
    protected $table = 'pub_cate';
    protected $primaryKey = 'pub_cate_id';
    public $timestamps = false;
    protected $guarded = [];

    public static function getPubCouListId()
    {
        $coiListId = self::lists('pub_coulist_id')->toArray();
        return $coiListId;
    }

    public static function  getPubVersionNum()
    {
        $versionNum = self::lists('pub_version_id')->toArray();
        return $versionNum;
    }

}

