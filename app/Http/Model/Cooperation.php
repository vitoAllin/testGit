<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;
use DB;
class Cooperation extends Model
{
    protected $table='order_son';
    protected $primaryKey='order_id';
    public $timestamps=false;
    protected $guarded=[];

    //显示合作商所有的授权信息
    public static function allAuthInfo($orderId)
    {
        $AuthInfo = self::where('order_pid', $orderId)->leftJoin('school', 'sch_order_son_oid', '=', 'order_id')->groupBy('order_id')->select('order_son.order_id as id', 'order_son.order_code as code', 'order_son.order_buymode as buymode', 'order_son.order_buyer as buyer', 'order_son.order_location as location','order_son.order_number as number', 'order_number_residue as residueNum', 'order_son.order_begin_time as startTime', 'order_son.order_over_time as overTime', 'order_province as province', 'order_city as city', 'order_district as district', 'order_create_time as createTime', DB::raw('count(*) as schoolCountNum '), 'school.sch_id as schoolId', 'order_son.order_pid as pid')->orderBy('order_id','desc')->paginate(10);
        return $AuthInfo;
    }

    //获取合作商授权订单的账号个数信息
    public static function getCooperationAuthNum($orderId){
        $orderNum = self::where('order_id', $orderId)->select('order_number', 'order_number_residue', 'order_buymode')->first();
        return $orderNum;
    }

}