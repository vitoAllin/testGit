<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;

class Authorization extends Model
{
    protected $table='order';
    protected $primaryKey='order_id';
    public $timestamps=false;
    protected $guarded=[];

    //获取授权订单(order)账号个数和剩余个数
    public static function getAuthNumber($orderId)
    {
        $orderNumber = self::where('order_id', $orderId)->select('order_number', 'order_number_residue')->first();
        return $orderNumber;
    }

    //判断剩余授权账号个数满不满足要求
    public static function canDistribute ($buyNumber, $orderId)
    {
        //判断订单个数能否继续分配
        $res = $buyNumber >= self::getAuthNumber($orderId);
        return $res;
    }

    //更新授权账号剩余个数
    public static function updateAuthNumber($buyNumber, $orderId){
       self:: where('order_id', $orderId)->decrement('order_number_residue', $buyNumber);
    }

    //
}