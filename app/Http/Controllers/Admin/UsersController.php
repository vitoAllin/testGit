<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/11/22
 * Time: 13:54
 */

namespace App\Http\Controllers\Admin;

use App\Http\Model\Authorization;
use App\Http\Model\Cooperation;
use App\Http\Model\School;
use App\Http\Model\User;
use App\Role;
use App\Permission;
use Symfony\Component\HttpFoundation\Request;
use Auth;
use DB;


class UsersController extends CommonController
{
    public function index()
    {
        //获取用户列表
        $data = User::where('user_class', '1')->orderBy('user_id','asc')->paginate(10);
        //遍历数据 与order/order_son表关联获取更多信息
        foreach( $data as $k => $v){
            if($v['user_oid'] != 0 && $v['user_flag'] == 1 && !$v['user_school_id']){
                //查找管理员所属的单位 机构/教育局/学校
                $org = Authorization::where('order_id', $v['user_oid'])->value('order_buyer');
                $v['org'] = $org;
            }else if($v['user_oid'] != 0 && $v['user_flag'] == 2 && !$v['user_school_id']){
                $org = Cooperation::where('order_id', $v['user_oid'])->value('order_buyer');
                $v['org'] = $org;
            }else if( $v['user_oid'] != 0 && $v['user_school_id']){
                $org = School::where('sch_id', $v['user_school_id'])->value('sch_name');
                $v['org'] = $org;
            }else if($v['user_oid'] == 0){
                $v['org'] = '后台超级管理员';
            }
        }
//        dd($data);
        return view('admin.users.index',compact('data'));
    }

    public function backGroundAdmin()
    {
        //获取用户列表
        $data = User::where('user_class', '2')->orderBy('user_id','asc')->paginate(10);
        //遍历数据 与order/order_son表关联获取更多信息
        foreach( $data as $k => $v){
            if($v['user_oid'] != 0 && $v['user_flag'] == 1 && !$v['user_school_id']){
                //查找管理员所属的单位 机构/教育局/学校
                $org = Authorization::where('order_id', $v['user_oid'])->value('order_buyer');
                $v['org'] = $org;
            }else if($v['user_oid'] != 0 && $v['user_flag'] == 2 && !$v['user_school_id']){
                $org = Cooperation::where('order_id', $v['user_oid'])->value('order_buyer');
                $v['org'] = $org;
            }else if( $v['user_oid'] != 0 && $v['user_school_id']){
                $org = School::where('sch_id', $v['user_school_id'])->value('sch_name');
                $v['org'] = $org;
            }else if($v['user_oid'] == 0){
                $v['org'] = '后台超级管理员';
            }
        }
//        dd($data);
        return view('admin.users.index',compact('data'));
    }

    //新增用户
    public function addUser(Request $request)
    {
        //TODO 对新增用户进行管理
    }
    
    //检查用户权限
    public function checkUserAuth()
    {
        $user = User::where('user_id', 1)->first();
        $userNow = Auth::user();
//        $userWho = Auth::login($user);
        $userStatus = Auth::check();
//        var_dump($userNow, $userStatus);
        $auth = $user->hasRole('admin');
//        var_dump($auth);
    }

    //修改用户信息页面
    public function updateUser($userId){
        $user = User::where('user_id', $userId)->first();
        //获取权限列表
        $roleAll = Role::select('id', 'name')->get();
        //获取已经绑定的权限
        $hasRoleId = DB::table('role_user')->where('user_id', $userId)->select('role_id')->get();
        return view('admin.users.user', compact('user'))->with(['roleAll'=>$roleAll, 'hasRoleId'=>$hasRoleId]);
    }

    //处理提交的用户修改信息
    public function editUser(Request $request){
        User::where('user_id', $request->user_id)->update(['user_name'=>$request->user_name, 'user_pass'=>encrypt($request->user_password)]);
        $user = User::find($request->user_id);
        //移除所有绑定的角色
        DB::table('role_user')->where('user_id', $request->user_id)->delete();
        if($request->roleItem){
            //通过权限 id 来给role分配所选权限
            foreach($request->roleItem as $v){
//                var_dump($v);
                //通过role id 来添加权限
                $user->roles()->attach([$v]);
            }
        }
        $data = [
            'status' => 1,
            'msg' => '操作成功！',
        ];
        return back()->with(['data'=>$data]);
    }

    //判断当前登录用户的角色
    public static function seeRoles($roles){
        $user = User::activeUser();
        return $user->hasRole($roles);
    }
}