<?php

namespace App\Http\Controllers\admin;

use App\Http\Model\Version;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use DB;

class VersionController extends Controller
{
    //显示当前的app
    public function index()
    {
        $versionInfo = Version::get();
        return view('admin.version.index')->with(['versionInfo'=>$versionInfo]);
    }


    //添加一条app更新信息
    public function add(Request $request)
    {
        $newVersion = new Version();
        $newVersion->version_code = $request->versionCode;
        //判断当前的version是否存在
        $versionNumAll = Version::lists('version_code')->toArray();
//        dd($versionAll, $request->appVersion);
        if(!in_array($request->versionCode, $versionNumAll)){
            $newVersion->version_code =  $request->versionCode;
            $newVersion->version_dis = $request->versionDis;
            $newVersion->version_remark = $request->versionRemark;
            $newVersion->version_time = date('Y-m-d H:i:s');
            $newVersion->save();
            //在bookConfig文件夹里面创建版本文件夹
            $hasFile = file_exists(base_path().'/bookconfig/'.$request->versionCode);
            if(!$hasFile){
                mkdir(base_path().'/bookconfig/'.$request->versionCode);
                return  redirect()->back()->withInput()->with(['errors'=>'版本文件夹创建成功']);
            }else{
                return  redirect()->back()->withInput()->with(['errors'=>'已经存在的版本文件夹，请检查']);
            }
            return redirect('admin/pub/pubVersion');
        }else{
            return  redirect()->back()->withInput()->with(['errors'=>'已经存在的APP版本号']);
        }
    }

    //修改发布版本号
    public function updateAppVersion(Request $request)
    {

    }


    //删除版本
    public function delete(Request $request)
    {
        $versionId = $request->versionId;
//       查找当前的版本下面有没有课件发布
        $vArr = DB::table('pub_cate')->lists('pub_version_id');
        if(in_array($versionId, $vArr)){
            return json_encode(['status'=>'0' ,'msg'=>'删除失败，当前版本号已存在发布课件']);
        }else{
            Version::where('version_id', $versionId)->delete();
            return json_encode(['status'=>'1' ,'msg'=>'删除版本成功']);
        }
    }
}
