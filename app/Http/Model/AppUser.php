<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;

class AppUser extends Model
{
    protected $table='appuser';
    protected $primaryKey='app_user_id';
    public $timestamps=false;
    protected $guarded=[];

    //获取APP用户的unionID列表
    public static function getUnionIdList()
    {
        $unionIdArr = self::lists('app_user_unionID')->toArray();
        return $unionIdArr;
    }

    //获取APP用户的电话列表
    public static function getPhoneNumList()
    {
        $unionIdArr = self::lists('app_user_phone')->toArray();
        return $unionIdArr;
    }

    //通过手机号查找APP用户
    public static function phoneToUser($phone)
    {
        $appUser = self::where('app_user_phone', $phone)
                 ->where('app_user_effective', 1)
//                ->select('app_user_id as uid', 'app_user_unionID as unionID', 'app_user_name as name', 'app_user_wechatname as weChatName',                     'app_user_logtimes as logTimes', 'app_user_iswechat as isWeChat', 'app_user_createtime as createTime',
//                    'app_user_phone as phone', 'app_user_pass as password', 'app_user_isbind as isBind', 'app_user_effective as userEffective')
                ->first();
        return $appUser;
    }

    //通过微信查找APP用户
    public static function weChatToUser($unionId)
    {
        $appUser = AppUser::where('app_user_unionID', $unionId)
                ->where('app_user_effective', 1)
                ->first();
        return $appUser;
    }
    /**
     * [getUserIdByUnionId 根据unionID获取userId]
     * @Author   Vito
     * @DateTime 2018-05-09T17:38:00+0800
     * @param    [type]                   $unionId [description]
     * @return   [type]                            [description]
     */
    public function getUserIdByUnionId($unionId)
    {
        $userId = self::where(['app_user_unionID' => $unionId, 'app_user_effective' => 1])->limit(1)->value('app_user_id');
        return $userId;
    }

    /**
     * [getUserIdByPhone 根据phone获取userId]
     * @Author   Vito
     * @DateTime 2018-05-09T17:39:30+0800
     * @param    [type]                   $phone [description]
     * @return   [type]                          [description]
     */
    public function getUserIdByPhone($phone)
    {
        $userId = self::where(['app_user_phone' => $phone, 'app_user_effective' => 1])->limit(1)->value('app_user_id');
        return $userId;
    }

}
