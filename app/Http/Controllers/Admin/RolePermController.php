<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/3/9
 * Time: 16:12
 */
namespace App\Http\Controllers\admin;


use Symfony\Component\HttpFoundation\Request;
use App\Role;
use App\Permission;
use DB;
class RolePermController extends CommonController
{
    public function getIndex()
    {
        //获取当前所有角色
        $allRole = Role::get();
        //回去当前的所有权限
        $allPermission = Permission::get();
//        var_dump($allRole);
        return view('admin.roleperm.index')->with(['allRole'=>$allRole, 'allPermission'=>$allPermission]);
    }

    public function info(Request $request)
    {
        if($request->requestView == 'role'){
            //获取所有权限
            $permissionAll = Permission::select('id', 'name')->get();
            return view('admin.roleperm.role')->with(['permAll'=>$permissionAll]);
        }else{
            return view('admin.roleperm.permission');
        }
    }

    //创建新的角色
    public function postCreaterole(Request $request)
    {
//        var_dump($request->all());
        $owner = new Role();
        $owner->name         = $request->role_name;
        $owner->display_name = $request->role_displayName; // optional
        $owner->description  = $request->role_description; // optional
        $owner->save();
        if($request->permItem){
            //通过权限 id 来给role分配所选权限
           foreach($request->permItem as $k => $v){
               //通过role id 来添加权限
               $owner->permissions()->attach([$v]);
           }
        }
        return redirect('admin/roleAndPerm/index');
    }

    //创建新的权限
    public function postCreateperm(Request $request)
    {
        $createPost = new Permission();
        $createPost->name         = $request->perm_name;
        $createPost->display_name = $request->perm_displayName; // optional
        $createPost->description  = $request->perm_description; // optional
        $createPost->save();
        return redirect('admin/roleAndPerm/index');
    }
    
    //修改角色 绑定权限
    public function getUpdaterole(Request $request){
        //获得角色信息
        $roleInfo = Role::where('id', $request->roleId)->first();
        //获得所有权限
        $permissionAll = Permission::select('id', 'name')->get();
        //获取已经选择的权限
        $hasPermId = DB::table('permission_role')->where('role_id', $request->roleId)->select('permission_id')->get();
        return view('admin.roleperm.roleupdate')->with(['roleInfo'=>$roleInfo, 'permAll'=>$permissionAll, 'hasPermId'=>$hasPermId]);
    }

    //提交修改角色
    public function postUpdaterole(Request $request){
//        dd($request->all());
        Role::where('id', $request->roleId)->update(['name'=>$request->role_name, 'description'=>$request->role_description, 'display_name'=>$request->role_displayName]);
        $roleItem = Role::find($request->roleId);
        //移除所有权限
        DB::table('permission_role')->where('role_id', $request->roleId)->delete();
        if($request->permItem){
            //通过权限 id 来给role分配所选权限
            foreach($request->permItem as $k => $v){
                //通过role id 来添加权限
                $roleItem->permissions()->attach([$v]);
            }
        }
        $data = [
            'status' => 1,
            'msg' => '操作成功！',
        ];
        return back()->with(['data'=>$data]);
    }

    //修改权限
    public function getUpdatepermission(Request $request){
        //获得权限信息
        $permInfo = Permission::where('id', $request->permId)->first();
        return view('admin.roleperm.permupdate')->with(['permInfo'=>$permInfo]);
    }

    //提交修改权限
    public function postUpdateperm(Request $request){
        Permission::where('id', $request->permId)->update(['name'=>$request->perm_name, 'description'=>$request->perm_description, 'display_name'=>$request->perm_displayName]);
        $data = [
            'status' => 1,
            'msg' => '操作成功！',
        ];
        return back()->with(['data'=>$data]);
    }
}