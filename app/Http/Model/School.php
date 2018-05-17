<?php

namespace App\Http\Model;

use App\Http\Controllers\Admin\SchoolController;
use App\Http\Requests\Request;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $table='school';
    protected $primaryKey='sch_id';
    public $timestamps=false;
    protected $guarded=[];

    //合作商授权学校 添加一条学校信息
    public function cooperationAddSchool($re, $auth){
        /*
         * $auth 总订单信息（order）
         */
        $this->sch_name = $re->organizationName;
        //学校代码
        $schoolCode = $this->getSchoolCode();
        $this->sch_code = $schoolCode;
        //学校账号个数
        $this->sch_invite_code_num = $re->buyNumber;
        //学校剩余授权个数
        $this->sch_invite_code_residue = $re->buyNumber;
        $this->sch_order_start = $re->act_start_time;
        $this->sch_order_stop = $auth->order_over_time;
        $this->sch_create_time = date('Y-m-d H:i:s');
        //生成学校这条消息的总订单（order.order_id）
        $this->sch_order_oid = $re->orderPid;
        //生成学校这条消息的订单 （order_son.order_id）
        $this->sch_order_son_oid = $auth->order_id;
        //账单状态 默认已付费
        $this->sch_order_status = 1;
        //生成学校邀请码
        $this->sch_invite_code = $this -> invitationCode($schoolCode);
        $this->save();
        return $this->sch_id;
    }

    //超级管理员授权学校 添加一条学校信息
    public function addSchool($re, $auth){
        $this->sch_name = $re->organizationName;
        //自动生成学校code
        $schoolControllerModel = new SchoolController();
        $schoolCode = $this -> getSchoolCode();
        //学校ID
        $this->sch_code = $schoolCode;
        $this->sch_invite_code_num = $re->buyNumber;
        $this->sch_invite_code_residue = $re->buyNumber;
        $this->sch_order_start = $re->act_start_time;
        $this->sch_order_stop = $auth->order_over_time;
        $this->sch_create_time = date('Y-m-d H:i:s');
        $this->sch_order_oid = $auth->order_id;
        $this->sch_order_status = 1;
        $this->sch_invite_code = $schoolControllerModel -> invitationCode($schoolCode);
        $this->save();
        return  $this->sch_id;
    }


    //生成学校id
    public function getSchoolCode(){
        $schoolCode = $this->GetRandDigit(4);
        return $schoolCode;
    }

    //生成邀请码
    public function  invitationCode(){
        //rand 6位随机数
        $randCode = $this-> GetRandDigit(6);
        $inviteCode = $randCode;
        return $inviteCode;
    }

    //生成随机数
    public function GetRandDigit($length){
        $str='0123456789';
        $len=strlen($str)-1;
        $randStr='';
        for($i=0;$i<$length;$i++){
            $num=mt_rand(0,$len);
            $randStr .= $str[$num];
        }
        //判断邀请码是否已经存在
        $inviteCodeList = School :: lists('sch_invite_code')->toArray();
        if(!in_array($randStr,$inviteCodeList)){
            return $randStr;
        }else{
            $this->GetRandDigit(6);
        }
    }
}
