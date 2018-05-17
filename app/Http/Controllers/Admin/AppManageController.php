<?php

namespace App\Http\Controllers\admin;

use App\Http\Model\AppManage;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class AppManageController extends Controller
{
    //显示当前的app
    public function index()
    {
        $appAll = AppManage::paginate(10);
        return view('admin.appManage.index')->with(['data'=>$appAll]);
    }

    //检查是否存在更新
    public function getNewVersion(Request $request){
        $appVersionNow = AppManage::where('app_version', AppManage::max('app_version'))->select('app_version', 'app_url')->first();
        $checkRes = ((float) $appVersionNow->app_version) <= $request->appversion ? false : $appVersionNow->app_url ;
        return (json_encode(['res' => 'checkAppUpdateRes', 'data' => ['result' => $checkRes, 'version'=>$appVersionNow->app_version]], JSON_UNESCAPED_SLASHES));
    }

    //添加一条app更新信息
    public function add(Request $request)
    {
        $newApp = new AppManage();
        $newApp->app_version = $request->appVersion;
        //判断当前的version是否存在
        $versionAll = AppManage::lists('app_version')->toArray();
//        dd($versionAll, $request->appVersion);
        if(!in_array($request->appVersion, $versionAll)){
            $newApp->app_url =  $request->appUrl;
            $newApp->app_createtime = date('Y-m-d H:i:s');
            $newApp->save();
            return redirect('admin/app/index');
        }else{
            return  redirect()->back()->withInput()->with(['errors'=>'已经存在的APP版本号']);
        }
    }

    //修改app的url
    public function updateUrl(Request $request)
    {
       $res = AppManage::where('app_id', $request->appId)->update(['app_url' => $request->appUrl]);
        if($res){
            $data = [
                'status' => 1,
                'msg' => '修改成功',
                'result'=>$request->appUrl
            ];
            return $data;
        }
    }

    public function delete(Request $request)
    {
       $res = AppManage::where('app_id', $request->appId)->delete();
        if($res){
            $data = [
                'status' => 1,
                'msg' => '删除成功',
            ];
            return $data;
        }
    }
}
