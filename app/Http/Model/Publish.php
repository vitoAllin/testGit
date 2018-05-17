<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class Publish extends Model
{
    protected $table = 'publish';
    protected $primaryKey = 'pub_id';
    public $timestamps = false;
    protected $guarded = [];
    const DOWNLOAD_SERVER_URL = 'http://develop.x-real.cn:89/';

    /**
     * @param $maxId
     * @param $versionId
     * @return string
     * 获取需要下载文件的信息
     * maxId 代表发来的请求id 寻找最大的
     */
    public function checkIdArr($maxId, $versionId)
    {
        //得到需要跟新的数据
//        $idArr = DB::select('select a.pub_id, a.pub_name, a.pub_url, a.book_id, a.pub_time, a.pub_title from sc_publish as a where pub_id = (select max(pub_id) from sc_publish where a.book_id=book_id) and a.pub_id > '.$maxId );
        $idArr = DB::select("select a.pub_id, a.pub_name,  CONCAT( '".self::DOWNLOAD_SERVER_URL."', a.pub_url) as pub_url, a.book_id, a.pub_time, a.pub_title from sc_publish as a where pub_id = (select max(pub_id) from sc_publish where a.book_id=book_id and pub_version = ".$versionId.")");
        return json_encode($idArr);
    }
}

