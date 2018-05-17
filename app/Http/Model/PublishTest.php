<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class Publishtest extends Model
{
    protected $table = 'publishtest';
    protected $primaryKey = 'pub_id';
    public $timestamps = false;
    protected $guarded = [];

    const DOWNLOAD_SERVER_URL = 'http://develop.x-real.cn:89/';
    public $testVar = 77;

    /**
     * @param $maxId
     * @return string
     * 获取需要下载文件的信息
     * maxId 代表发来的请求id 寻找最大的
     */
    public function checkIdArr($maxId)
    {
        //得到需要跟新的课本数据
        //判断是否是测试版本
        $idArr = DB::select("select a.pub_id, a.pub_name, CONCAT( '".self::DOWNLOAD_SERVER_URL."', a.pub_url) as pub_url, a.book_id,  a.pub_time , a.pub_title from sc_publishtest as a where pub_id = (select max(pub_id) from sc_publishtest where a.book_id=book_id) and a.pub_id > ".$maxId);
        return json_encode($idArr);
    }
}

