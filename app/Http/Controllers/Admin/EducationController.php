<?php

namespace App\Http\Controllers\Admin;

use App\Http\Model\Authorization;
use App\Http\Model\Cooperation;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Session;
use DB;
use Auth;


class EducationController extends CommonController
{

    public function index(){
        $user = Auth::user();
        $userOid = $user->user_oid ;
        if($user->hasRole('educationAdmin')){
            $orderInfo = Authorization::where('order_id', $userOid)->first();
            return view('admin.autheducation.index')->with(['orderInfo'=>$orderInfo]);
        }
    }

    //显示合作信息
    public function educationInfo(){
        $user = Auth::user();
        $userOid = $user->user_oid ;
        if($user->hasRole('educationAdmin')){
            //判断用户是超级管理员授权的，还是通过合作商管理员授权的
            if($user->user_flag == 1){
                //超级管理员授权
                $orderInfo = Authorization::where('order_id', $userOid)->first();
            }else if($user->user_flag == 2){
                //合作商管理员授权
                $orderInfo = Cooperation::where('order_id', $userOid)->first();
            }
            return view('admin.autheducation.authinfo')->with(['orderInfo'=>$orderInfo]);
        }
    }

    //授权列表页面
    public function authList(){
        $user = Auth::user();
        $userOid = $user->user_oid ;
        if($user->hasRole('educationAdmin')){
            //更新之后
            $updateMsg = Session::get('updateMsg');
            //获取列表信息
            //判断是超级管理员授权 还是合作商授权
            if($user->user_flag == 1){
                //超级管理员授权
                $AuthInfo = Authorization::where('order_id', $userOid)->leftJoin('school', 'sch_order_oid', '=', 'order_id')->groupBy('order_id')->select('order.order_id as id', 'order.order_code as code','order.order_buymode as buymode', 'order.order_buyer as buyer', 'order.order_location as location','order.order_number as number', 'order_number_residue as residueNum', 'order.order_begin_time as startTime', 'order.order_over_time as overTime', 'order_province as province', 'order_city as city', 'order_district as district', 'order_create_time as createTime', DB::raw('count(*) as schoolCountNum '), 'school.sch_id as schoolId')->orderBy('order_id','desc')->paginate(10);
                return view('admin.autheducation.list')->with(['AuthListInfo' => $AuthInfo, 'updateMsg'=> $updateMsg, 'authProperty'=>1]);
            }else if($user->user_flag == 2){
                //合作商管理员授权
                $AuthInfo = Cooperation::where('order_id', $userOid)->leftJoin('school', 'sch_order_son_oid', '=', 'order_id')->groupBy('order_id')->select('order_son.order_id as id', 'order_son.order_code as code', 'order_son.order_buymode as buymode', 'order_son.order_buyer as buyer', 'order_son.order_location as location','order_son.order_number as number', 'order_number_residue as residueNum', 'order_son.order_begin_time as startTime', 'order_son.order_over_time as overTime', 'order_province as province', 'order_city as city', 'order_district as district', 'order_create_time as createTime', DB::raw('count(*) as schoolCountNum '), 'school.sch_id as schoolId', 'order_son.order_pid as pid')->orderBy('order_id','desc')->paginate(10);
                return view('admin.autheducation.list')->with(['AuthListInfo' => $AuthInfo, 'updateMsg'=> $updateMsg, 'authProperty'=>2]);
            }
        }
    }

    //展示授权订单
    public function show(Request $request){
        //获取要修改的授权id
        $authId =  $request->authId;
//        $orderInfo = Cooperation::where('order_id', $authId)->select('*')->first();
        $user = Auth::user();
        $userOid = $user->user_oid;
        //判断用户是超级管理员授权的，还是通过合作商管理员授权的
        if($user->user_flag == 1){
            //超级管理员授权
            $orderInfo = Authorization::where('order_id', $authId)->select('*')->first();
        }else if($user->user_flag == 2){
            //合作商管理员授权
            $orderInfo = Cooperation::where('order_id', $userOid)->first();
        }
        //查找数据库展示
        return view('admin.autheducation.show')->with(['orderInfo'=>$orderInfo, 'currentPage'=>$request->currentPage]);
    }




}