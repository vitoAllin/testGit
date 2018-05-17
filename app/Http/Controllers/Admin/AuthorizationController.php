<?php

namespace App\Http\Controllers\Admin;

use App\Http\Model\Authorization;
use App\Http\Model\InviteCode;
use App\Http\Model\Managers;
use App\Http\Model\School;
use App\Http\Model\SchoolManagerRelation;
use App\Http\Model\User;
use App\Role;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use DB;


class AuthorizationController extends CommonController
{
    //授权详情显示页面
    public function index(){
        return view('admin.authorization.index');
    }

    //授权列表页面
    public function authList(){
        //更新之后
        $updateMsg = Session::get('updateMsg');
        //获取列表信息
        $AuthInfo = Authorization::leftJoin('school', 'sch_order_oid', '=', 'order_id')->groupBy('order_id')->select('order.order_id as id', 'order.order_code as code','order.order_buymode as buymode', 'order.order_buyer as buyer', 'order.order_location as location','order.order_number as number', 'order_number_residue as residueNum', 'order.order_begin_time as startTime', 'order.order_over_time as overTime', 'order_province as province', 'order_city as city', 'order_district as district', 'order_create_time as createTime', DB::raw('count(*) as schoolCountNum '), 'school.sch_id as schoolId')->orderBy('order_id','desc')->paginate(10);
        return view('admin.authorization.list')->with(['AuthListInfo' => $AuthInfo, 'updateMsg'=> $updateMsg]);
    }

    //添加授权信息
    public function add(Request $request){
        //验证提交的数据
//        dd($request);
        $rules = [
            'province' => 'required',
//            'city' => 'required',
//            'district' => 'required',
            'buyMode' => 'required',
            'organizationName' => 'required',
            'act_start_time' => 'required',
            'buyNumber' => 'required',
        ];

        $message = [
            'province.required'=>'地区不能为空！',
            'buyMode.required'=>'请选择购买模式！',
            'act_start_time.required'=>'账号开始时间不能为空！',
            'organizationName.required'=>'购买机构不能为空！',
            'buyNumber.required'=>'购买数量不能为空！'
        ];

        $this->validate($request, $rules, $message);
        //实例 授权表
        $auth = new Authorization();
        $auth->order_code = 'o'.date('YmdHis').$this->GetRandStr(6);
        $auth->order_buyer = $request->organizationName;
        $auth->order_number = $request->buyNumber;
        //判断若是学校购买 剩余个数直接为0
        if($request->buyMode == 2){
            $auth->order_number_residue = 0;
        }else{
            $auth->order_number_residue = $request->buyNumber;
        }
        $auth->order_linkman_name = $request->linkmanName;
        $auth->order_linkman_phone = $request->linkmanPhone;
        $auth->order_org_mgr_name = $request->orgMgrName;
        $auth->order_org_mgr_phone = $request->orgMgrPhone;
        $auth->order_buymode = $request->buyMode;

        $auth->order_begin_time = $request->act_start_time;
        $auth->order_create_time = date('Y-m-d H:i:s');
        if($request->act_stop_time){
            $auth->order_over_time = $request->act_stop_time;
        }
        //判断有效期是否自定义
        if($request->validTime != 0){
            $year = $request->validTime;
            $validTime  = strtotime($request->act_stop_time) - strtotime($request->act_start_time);
            $lastDate = date("Y-m-d H:i",strtotime("+".$year." year",strtotime($request->act_start_time)));
            $auth->order_over_time = $lastDate;
        }
        $auth->order_life = $request->validTime;
        $auth->order_location = $request->province.$request->city.$request->district;
        $auth->order_province = $request->province;
        $auth->order_city = $request->city;
        $auth->order_district = $request->district;
        $res = $auth->save();
        if($res){
            $school = null;
            //判断是否是学校购买
            if($request->buyMode == 2){
                //实例 学校表
                $school = new School();
                $school->addSchool($request, $auth);
                //实例 管理员表
                $manager = new Managers();
                $manager->addManager($request);
                //实例关系表
                $schRelationMgr = new SchoolManagerRelation();
                $schRelationMgr->addRelation($school->sch_id, $manager->mgr_id);
            }
            //用户表插入管理员数据
            $user = new User();
            $user->user_name = $request->orgMgrName;
            $user->user_pass = encrypt(substr($request->orgMgrPhone, -6));
            $user->user_oid = $auth->order_id;
            $user->user_class = '1';
            if($request->buyMode == 2){
                $user->user_school_id = $school->sch_id;
            }
            $user->save();
            //设置权限
            if($request->buyMode == 1){
                $roleId = Role::where('name', 'educationAdmin')->value('id');
                $user->roles()->attach($roleId);
            }elseif($request->buyMode == 2){
                $roleId = Role::where('name', 'schoolAdmin')->value('id');
                $user->roles()->attach($roleId);
            }elseif($request->buyMode == 3){
                $roleId = Role::where('name', 'cooperationAdmin')->value('id');
                $user->roles()->attach($roleId);
            }
            return redirect('admin/authorization/list');
        }else{
            return back()->with(['errors'=>'数据添加失败，请稍后再试']);
        }
    }

    //展示授权订单
    public function show(Request $request){
        //获取要修改的授权id
        $authId =  $request->authId;
        $orderInfo = Authorization::where('order_id', $authId)->select('*')->first();
        //查找数据库展示
        return view('admin.authorization.show')->with(['orderInfo'=>$orderInfo, 'currentPage'=>$request->currentPage]);
    }

    //得到随机数
    public function GetRandStr($length){
        $str='0123456789';
        $len=strlen($str)-1;
        $randStr='';
        for($i=0;$i<$length;$i++){
            $num=mt_rand(0,$len);
            $randStr .= $str[$num];
        }
        return $randStr;
    }

    //手机 邀请码 绑定页面
    public function bind(){
        //绑定之后
        $bindMsg = Session::get('bindMsg');

        $invite = InviteCode::leftJoin('user as u', 'u.user_id', '=', 'invitecode.invite_user_id')
                ->select('invite_id as id', 'invite_code as inviteCode', 'teacher_phone as teacherPhone', 'create_time as createTime', 'u.user_name as inviteUser')->paginate(10);
        return view('admin/authorization/bind')->with(['allInvite'=>$invite, 'bindMsg'=>$bindMsg]);
    }

    //绑定邀请码操作
    public function addBind(Request $request){
        $inviteCode = trim($request->inviteCode);
        $phoneNumber = trim($request->phoneNumber);
        $invite = new InviteCode();
        //获取学校id
        $schoolId = School::where('sch_invite_code', $inviteCode)->value('sch_id');
        if(!$schoolId){
            return back()->with(['bindMsg'=>2]);
        }

        //判断邀请码是否够用 更新学校表 剩余邀请码的数据
        if(School::where('sch_invite_code', $inviteCode)->value('sch_invite_code_residue') > 0){
            $invite->invite_code = $inviteCode;
            $invite->teacher_phone = $phoneNumber;
            $invite->invite_user_id =  User::activeUser()->user_id;
            $invite->create_time = date('Y-m-d H:i:s');
            $invite->school_id = $schoolId;
            $res = $invite->save();
            if($res){
                School::where('sch_invite_code', $inviteCode)->decrement('sch_invite_code_residue', 1);
            }
            $bindMsg = 1;
        }else{
            $bindMsg = 3;
        }
        return redirect('admin/authorization/bind/')->with(['bindMsg'=>$bindMsg]);
    }

    //修改订单方法
    public function update(Request $request){
//        dd($request);
        $rules = [
            'province' => 'required',
            'buyMode' => 'required',
            'organizationName' => 'required',
            'act_start_time' => 'required',
            'buyNumber' => 'required',
        ];

        $message = [
            'province.required'=>'地区不能为空！',
            'buyMode.required'=>'请选择购买模式！',
            'act_start_time.required'=>'账号开始时间不能为空！',
            'organizationName.required'=>'购买机构不能为空！',
            'buyNumber.required'=>'购买数量不能为空！'
        ];

        if($request->validTime != 0){
            $year = $request->validTime;
            $validTime  = strtotime($request->act_stop_time) - strtotime($request->act_start_time);
            $lastDate = date("Y-m-d H:i",strtotime("+".$year." year",strtotime($request->act_start_time)));
        }else{
            $lastDate = $request->act_stop_time;
        }
        //若修改购买账号个数 修改剩余账号个数
        $orderResidue = Authorization::where('order_id', $request->orderId)->select('order_number', 'order_number_residue')->first();
        //判断修改之后的账号是否小于剩余的个数
        $changeNum = $request->buyNumber - $orderResidue->order_number;
        //判断修改的个数若小于已经分配的个数，则不允许修改
//        dd($orderResidue->order_number - $orderResidue->order_number_residue);
        if($request->buyMode != 2){
            if($request->buyNumber >= (($orderResidue->order_number) - ($orderResidue->order_number_residue)) ){
                    Authorization::where('order_id', $request->orderId)->increment('order_number_residue', $changeNum);
            }else{
                return redirect()->action('Admin\AuthorizationController@authList')->with(['updateMsg'=>5]);
            }
        }else{
            $schoolInviteNum = School::where('sch_order_oid', $request->orderId)->select('sch_invite_code_num', 'sch_invite_code_residue')->first();
            //判断修改的个数若小于已经分配的个数，则不允许修改
            if($request->buyNumber >= (($schoolInviteNum->order_number) - ($schoolInviteNum->order_number_residue))){
                School::where('sch_order_oid', $request->orderId)->increment('sch_invite_code_residue', $changeNum, ['sch_invite_code_num'=>$request->buyNumber]);
            }else{
                return redirect()->action('Admin\AuthorizationController@authList')->with(['updateMsg'=>5]);
            }
        }
        $this->validate($request, $rules, $message);
        //更新这条信息
        $updateRes = Authorization::where('order_id', $request->orderId)
                    ->update(['order_province'=>$request->province, 'order_city'=>$request->city,
                        'order_district'=>$request->district, 'order_buymode'=>$request->buyMode,
                        'order_buyer'=>$request->organizationName, 'order_number'=>$request->buyNumber,
                        'order_begin_time'=>$request->act_start_time, 'order_over_time'=>$lastDate,
                        'order_life'=>$request->validTime, 'order_linkman_name'=>$request->linkmanName,
                        'order_linkman_phone'=>$request->linkmanPhone, 'order_org_mgr_name'=>$request->orgMgrName,
                        'order_org_mgr_phone'=>$request->orgMgrPhone
                    ]);
//        return redirect('admin/authorization/list')->with(['updateMsg'=>1]);
        return redirect()->action('Admin\AuthorizationController@authList')->with(['updateMsg'=>1]);
    }



}