<?php

namespace App\Http\Controllers\Admin;

use App\Http\Model\Authorization;
use App\Http\Model\Cooperation;
use App\Http\Model\InviteCode;
use App\Http\Model\Managers;
use App\Http\Model\School;
use App\Http\Model\SchoolManagerRelation;
use App\Http\Model\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Role;
use Auth;


class CooperationController extends CommonController
{
    //添加 合作商授权订单页面
    public function index(Request $request)
    {
        $orderInfo = null;
        //当前管理员的标识 1代表超级管理员 2代表合作商管理员
        $authFlag = null;
        if(UsersController::seeRoles('cooperationAdmin')){
            $userOid = User::activeUser()->user_oid ;
            $orderInfo = Authorization::where('order_id', $userOid)->first();
            $authFlag = 2;
        }elseif(UsersController::seeRoles('admin')){
            //如果是后台管理员给合作商添加新的订单
            $orderInfo = Authorization::where('order_id',$request->authId )->first();
            $authFlag = 1;
        }
        return view('admin.authcooperation.index')->with(['orderInfo'=>$orderInfo, 'authFlag'=>$authFlag]);
    }

    //合作商管理员 显示合作信息
    public function cooperationInfo(){
        $userOid = User::activeUser()->user_oid ;
        $orderInfo = Authorization::where('order_id', $userOid)->first();
        return view('admin.authcooperation.authinfo')->with(['orderInfo'=>$orderInfo]);
    }

    //授权列表页面
    public function authList(Request $request){
        //判断是否是合作商用户登录后台,是否拥有合作商权限，展示该用户的信息
        $user = User::activeUser();
        $userOid = $user->user_oid ;
        if($user->hasRole('cooperationAdmin')){
            //更新之后
            $updateMsg = Session::get('updateMsg');
            //合作商 获取列表信息
            $AuthInfo = Cooperation::allAuthInfo($userOid);
            return view('admin.authcooperation.list')->with(['AuthListInfo' => $AuthInfo, 'updateMsg'=> $updateMsg]);
        }else{
            //更新之后
            $updateMsg = Session::get('updateMsg');
            //超级管理员 获取列表信息
            $AuthInfo = Cooperation::allAuthInfo($request->authId);
            return view('admin.authcooperation.list')->with(['AuthListInfo' => $AuthInfo, 'updateMsg'=> $updateMsg, 'authId'=>$request->authId]);
        }
    }

    //添加授权信息
    public function add(Request $request){
//        dd($request->all());
        //验证提交的数据
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

        $this->validate($request, $rules, $message);
        $canBuy = Authorization::canDistribute($request->buyNumber  , $request->orderPid);
        //判断是否满足个数要求
        if($canBuy){
            return back()->with(['errors'=>'账号个数不足，请修改个数']);
        }
        //实例 合作商（子）授权表
        $auth = new Cooperation();
        $auth->order_code = 'oson'.date('YmdHis').$this->GetRandStr(6);
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
        $auth->order_pid = $request->orderPid;

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
            //判断是否是学校购买
            if($request->buyMode == 2){
                //添加数据到学校表
                $school = new School();
                $school->cooperationAddSchool($request, $auth);
                $schoolId = $school->sch_id;
                //管理员表
                $manager = new Managers();
                $manager->addManager($request);

                //管理员和学校关系表
                $schRelationMgr = new SchoolManagerRelation();
                $schRelationMgr->addRelation($school->sch_id, $manager->mgr_id);
            }
            //用户表插入管理员数据
            $user = new User();
            $user->user_name = $request->orgMgrName;
            $user->user_pass = encrypt(substr($request->orgMgrPhone, -6));
            $user->user_oid = $auth->order_id;
            //用户是被超级管理员授权的还是被合作商管理员授权的
            $user->user_flag = 2;
            //用户的学校ID 如果是学校授权则有，否则设置为null
            $user->user_school_id = isset($schoolId) ? $schoolId : null;
            $user->user_class = '1';
            $user->save();
            //添加权限
            if($request->buyMode == 1){
                $roleId = Role::where('name', 'educationAdmin')->value('id');
                $user->roles()->attach($roleId);
            }elseif($request->buyMode == 2){
                $roleId = Role::where('name', 'schoolAdmin')->value('id');
                $user->roles()->attach($roleId);
            }
            Authorization::updateAuthNumber($request->buyNumber  , $request->orderPid);
            //判断是超级管理员添加的还是合作商管理员添加的
            if(UsersController::seeRoles('cooperationAdmin')){
                return redirect('admin/authcooperation/list');
            }elseif(UsersController::seeRoles('admin')){
                return redirect('admin/authcooperation/list/?authId='.$request->orderPid);
            }
            //更新order表里面账号剩余的个数

        }else{
            return back()->with(['errors'=>'数据添加失败，请稍后再试']);
        }
    }

    //展示授权订单
    public function show(Request $request){
        //管理员分类
        $authFlag = null;
        //获取要修改的授权id
        $authId =  $request->authId;
        $orderInfo = Cooperation::where('order_id', $authId)->select('*')->first();
        //获取当前用户
        if(UsersController::seeRoles('admin')){
            $authFlag = 1;
        }elseif(UsersController::seeRoles('cooperationAdmin')){
            $authFlag = 2;
        }
        return view('admin.authcooperation.show')->with(['orderInfo'=>$orderInfo, 'currentPage'=>$request->currentPage, 'authFlag'=>$authFlag]);
    }

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
        $invite = InviteCode::select('invite_id as id', 'invite_code as inviteCode', 'teacher_phone as teacherPhone', 'create_time as createTime')->paginate(10);
        return view('admin/authcooperation/bind')->with(['allInvite'=>$invite, 'bindMsg'=>$bindMsg]);
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
            $invite->create_time = date('Y-m-d H:i:s');
            $invite->school_id = $schoolId;
            $invite->invite_user_id = User::activeUser()->user_id;
            $res = $invite->save();
            if($res){
                School::where('sch_invite_code', $inviteCode)->decrement('sch_invite_code_residue', 1);
            }
            $bindMsg = 1;
        }else{
            $bindMsg = 3;
        }
        return redirect('admin/authcooperation/bind/')->with(['bindMsg'=>$bindMsg]);
    }

    //修改订单方法
    public function update(Request $request){
//        dd($request->all());
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
        $this->validate($request, $rules, $message);

        //修改的个数
        $orderNum = Cooperation::getCooperationAuthNum($request->orderId);
        $changeNum = $request->buyNumber - $orderNum->order_number;
        //获取总订单剩余的账号个数
        $p_orderNum= Authorization::getAuthNumber($request->orderPid);
        //判断购买模式，学校和教育局的修改方式不同
        if($orderNum->order_buymode == 2){
            //学校购买 修改授权个数
            if($changeNum <= ($p_orderNum->order_number_residue) && $request->buyNumber && $request->buyNumber >= 0){
                //学校表修改
                School::where('sch_order_son_oid', $request->orderId)->where('sch_order_oid', $request->orderPid)->increment('sch_invite_code_num', $changeNum);
                Authorization::where('order_id', $request->orderPid)->decrement('order_number_residue', $changeNum);
            }else{
                if(UsersController::seeRoles('cooperationAdmin')){
                    return redirect()->action('Admin\CooperationController@authList')->with(['updateMsg'=>5]);
                }elseif(UsersController::seeRoles('admin')){
                    return redirect('admin/authcooperation/list/?authId='.$request->orderPid);
                }
            }
        }else{
            //教育局购买修改授权个数
            //判断修改的个数是否大于当前总订单剩余的个数 不允许大于; 判断修改的个数若小于已经分配的个数，则不允许修改
            if($changeNum <= ($p_orderNum->order_number_residue) && $request->buyNumber >= (($orderNum->order_number) - ($orderNum->order_number_residue))){
                Authorization::where('order_id', $request->orderPid)->decrement('order_number_residue', $changeNum);
                Cooperation::where('order_id', $request->orderId)->increment('order_number_residue', $changeNum);
            }else{
                if(UsersController::seeRoles('cooperationAdmin')){
                    return redirect()->action('Admin\CooperationController@authList')->with(['updateMsg'=>5]);
                }elseif(UsersController::seeRoles('admin')){
                    return redirect('admin/authcooperation/list/?authId='.$request->orderPid);
                }
            }
        }
        //更新这条信息
        $updateRes = Cooperation::where('order_id', $request->orderId)
            ->update(['order_province'=>$request->province, 'order_city'=>$request->city,
                'order_district'=>$request->district, 'order_buymode'=>$request->buyMode,
                'order_buyer'=>$request->organizationName, 'order_number'=>$request->buyNumber,
                'order_begin_time'=>$request->act_start_time, 'order_over_time'=>$lastDate,
                'order_life'=>$request->validTime, 'order_linkman_name'=>$request->linkmanName,
                'order_linkman_phone'=>$request->linkmanPhone, 'order_org_mgr_name'=>$request->orgMgrName,
                'order_org_mgr_phone'=>$request->orgMgrPhone
            ]);
        //        dd($updateRes);
        if(UsersController::seeRoles('cooperationAdmin')){
            return redirect()->action('Admin\CooperationController@authList')->with(['updateMsg'=>1]);
        }elseif(UsersController::seeRoles('admin')){
            return redirect('admin/authcooperation/list/?authId='.$request->orderPid)->with(['updateMsg'=>1]);
        }
    }

    //超级管理员 显示所有合作商授权订单
    public function allCooperationOrder(){
        //更新之后
        $updateMsg = Session::get('updateMsg');
        //获取数据
        $allCooperationOrder = Cooperation::leftJoin('school', 'sch_order_son_oid', '=', 'order_id')
            ->leftJoin('order', 'order_son.order_pid', '=', 'order.order_id')
            ->groupBy('order_son.order_id')->select('order_son.order_id as id', 'order_son.order_code as code','order_son.order_buymode as buymode', 'order_son.order_buyer as buyer', 'order_son.order_location as location','order_son.order_number as number', 'order_son.order_number_residue as residueNum', 'order_son.order_begin_time as startTime', 'order_son.order_over_time as overTime', 'order_son.order_province as province', 'order_son.order_city as city', 'order_son.order_district as district', 'order_son.order_create_time as createTime', DB::raw('count(*) as schoolCountNum '), 'school.sch_id as schoolId', 'order.order_buyer as cooperationBuyer')->orderBy('order_son.order_id','desc')->paginate(10);
//        dd($allCooperationOrder);
        return view('admin.cooperation.allorder')->with(['allCooperationOrder' => $allCooperationOrder, 'updateMsg'=>$updateMsg]);
    }
}