<?php
//学校管理员 Controller

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


class SchoolAdminController extends CommonController
{
    public function login(){
        return view('admin.userindexpage.schoolmag');
    }

    public function index()
    {
        $user = Auth::user();
        if ($user->hasRole('cooperationAdmin')) {
            $userOid = $user->user_oid;
            $orderInfo = Authorization::where('order_id', $userOid)->first();
            return view('admin.authschool.index')->with(['orderInfo' => $orderInfo]);
        }
    }

    //显示合作信息
    public function schoolInfo()
    {
        $user = Auth::user();
        $userSchoolId = $user->user_school_id;
        if($user->hasRole('schoolAdmin')){
            $schoolInfo = School::where('school.sch_id', $userSchoolId)->select('*')->first();
            return view('admin.authschool.authinfo')->with(['orderInfo' => $schoolInfo]);
        }
    }

    //授权列表页面
    public function authList()
    {
        //判断是否是合作商用户登录后台,是否拥有合作商权限，展示该用户的信息
        $user = Auth::user();
        $userOid = $user->user_oid;
        if ($user->hasRole('cooperationAdmin')) {
            //更新之后
            $updateMsg = Session::get('updateMsg');
            //获取列表信息
            $AuthInfo = Cooperation::where('order_pid', $userOid)->leftJoin('school', 'sch_order_son_oid', '=', 'order_id')->groupBy('order_id')->select('order_son.order_id as id', 'order_son.order_code as code', 'order_son.order_buymode as buymode', 'order_son.order_buyer as buyer', 'order_son.order_location as location', 'order_son.order_number as number', 'order_number_residue as residueNum', 'order_son.order_begin_time as startTime', 'order_son.order_over_time as overTime', 'order_province as province', 'order_city as city', 'order_district as district', 'order_create_time as createTime', DB::raw('count(*) as schoolCountNum '), 'school.sch_id as schoolId', 'order_son.order_pid as pid')->orderBy('order_id', 'desc')->paginate(10);
//            dd($AuthInfo);
            return view('admin.authschool.list')->with(['AuthListInfo' => $AuthInfo, 'updateMsg' => $updateMsg]);
        }
    }

    //手机 邀请码 绑定页面
    public function bind()
    {
        //绑定之后
        $bindMsg = Session::get('bindMsg');
        $userId =  User::activeUser()->user_id;
        //因为是学校管理员 所以一定有school_id
        $userSchoolId = User::activeUser()->user_school_id;
        //学校邀请码
        $inviteCode = School::where('sch_id', $userSchoolId)->value('sch_invite_code');
//        dd($userSchoolId);
        //TODO 学校的邀请码需要是本学校的
        $invite = InviteCode::where('invite_user_id', $userId)->select('invite_id as id', 'invite_code as inviteCode', 'teacher_phone as teacherPhone', 'create_time as createTime')->paginate(10);
        return view('admin/authschool/bind')->with(['allInvite' => $invite, 'bindMsg' => $bindMsg, 'schoolId'=>$inviteCode]);
    }

    //绑定邀请码操作
    public function addBind(Request $request)
    {
        $inviteCode = trim($request->inviteCode);
        $phoneNumber = trim($request->phoneNumber);
        $invite = new InviteCode();
        //获取学校id
        $schoolId = School::where('sch_invite_code', $inviteCode)->value('sch_id');
        if (!$schoolId) {
            return back()->with(['bindMsg' => 2]);
        }

        //判断邀请码是否够用 更新学校表 剩余邀请码的数据
        if (School::where('sch_invite_code', $inviteCode)->value('sch_invite_code_residue') > 0) {
            $invite->invite_code = $inviteCode;
            $invite->teacher_phone = $phoneNumber;
            $invite->create_time = date('Y-m-d H:i:s');
            $invite->school_id = $schoolId;
            //添加绑定 管理员的userId
            $invite->invite_user_id =  User::activeUser()->user_id;
            $res = $invite->save();
            if ($res) {
                School::where('sch_invite_code', $inviteCode)->decrement('sch_invite_code_residue', 1);
            }
            $bindMsg = 1;
        } else {
            $bindMsg = 3;
        }
        return redirect('admin/authschool/bind/')->with(['bindMsg' => $bindMsg]);
    }



}