<?php

namespace App\Http\Controllers\admin;

use App\Http\Model\InviteCode;
use App\Http\Model\School;
use App\Http\Model\Teacher;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
//use Illuminate\Support\Facades\Redis;
use Redis;

class TeacherController extends Controller
{
    //检查是否存在此用户
    public function index(Request $request){
        $unionIdArr = Teacher::lists('tc_unionID as unionId');
//        dd($unionIdArr);
        $isExistTc = in_array($request->unionID, $unionIdArr->toArray());
//        var_dump($isExistTc);
        $checkRes = $isExistTc ? true : false ;
//        if($checkRes){
//            echo 'here';
//        }
//        dd($checkRes);
//        dd($isExistTc, $checkRes, $unionIdArr->toArray());
        return (json_encode(['type' => 'checkTeacherRes', 'data' => ['result' => $checkRes]]));
    }

    //创建教师用户
    public function createTc(Request $request){
//        服务器版本
//        $msg = json_decode($request->create);
//        $tcInfo = [];
//        $tcInfo['tc_wechatname'] = $msg-> data -> wcname;
//        $tcInfo['tc_unionID'] = $msg-> data -> unionID;
//        $tcInfo['tc_logtimes'] = 1;
//        $tcInfo['tc_iswechat'] = 1;
//        $createRes = Teacher::create($tcInfo);
//        return (json_encode(['type' => 'createTcRes', 'data' => ['result' => $createRes]]));

//        本地版本
//        $unionIdList = Teacher::lists('unionID')->toArray();
            $tcInfo = [];
            $tcInfo['tc_wechatname'] = $request -> wcname;
            $tcInfo['tc_unionID'] = $request -> unionID;
            $tcInfo['tc_logtimes'] = 1;
            $tcInfo['tc_iswechat'] = 1;
            $tcInfo['tc_createtime'] = date('Y-m-d H:i:s');
            $createRes = Teacher::create($tcInfo);
            return (json_encode(['type' => 'createTcRes', 'data' => ['result' => $createRes]]));
    }

    //若教师用户已经存在，返回教师用户信息
    public function downloadTc(Request $request)
    {
//        服务器版本
//        $msg = json_decode($request->download);
//        $unionID = $msg-> data -> unionID;
//        $tcInfo = Teacher::select('tc_name', 'tc_unionID', 'tc_wechatname', 'tc_logtimes')->where('tc_unionID', $unionID)->first();
//        return (json_encode(['type' => 'downloadTcRes', 'data' => ['result' => $tcInfo]]));

//        本地版本
        $tcInfo = Teacher::select('tc_name', 'tc_unionID', 'tc_wechatname', 'tc_logtimes', 'tc_school')->where('tc_unionID', $request -> unionID)->first();
//        dd($tcInfo);
        return (json_encode(['type' => 'downloadTcRes', 'data' => ['result' => $tcInfo]]));
    }

    //教师用户列表
    public function show(){
        $data = Teacher::orderBy('tc_id','desc')->get();
        return view('admin.teacher.index',compact('data'));
    }

    //测试
    public function test(Request $request){
       return view('admin.test');
    }
    
    //教师邀请码绑定
    public function bindInviteCode(Request $request){
        $inviteInfo = json_decode($request->bind);
        $inviteCodeList = School::lists('sch_invite_code')->toArray();
        $invitePhoneList = InviteCode::lists('teacher_phone')->toArray();
        $schoolId = School::where('sch_invite_code', $inviteInfo->data->inviteCode)->value('sch_id');

        //判断邀请码是否有效
        if(!in_array($inviteInfo->data->inviteCode, $inviteCodeList)){
            return json_encode(['type' => 'bindResultCodeNone', 'data' => ['message' => '绑定失败，输入的邀请码不正确', 'status'=>1]]);
        }

        //邀请码有效的情况下 判断情况
        //1.用户已经是注册用户并且自行绑定了
        $isInvited = Teacher::where('tc_unionID', $inviteInfo->data->unionID)->value('tc_school');
        if($isInvited){
            return json_encode(['type' => 'bindResultHasBind', 'data' => ['message' => '您已经绑定']]);
        }

        //2 后台已经绑定了 但是用户没有绑定
        if(in_array($inviteInfo->data->invitePhone, $invitePhoneList) && !(InviteCode::where('teacher_phone', $inviteInfo->data->invitePhone)->value('unionID'))){
            InviteCode::where('teacher_phone',  $inviteInfo->data->invitePhone)->delete();
            Teacher::where('tc_unionID', $inviteInfo->data->unionID)->update(['tc_school'=>$schoolId]);
            School::where('sch_invite_code', $inviteInfo->data->inviteCode)->decrement('sch_invite_code_residue', 1);
            return json_encode(['type' => 'bindResultOver', 'data' => ['message' => '绑定成功']]);
        }

        //3 用户自己做绑定，后台没有提前绑定
        if(!in_array($inviteInfo->data->invitePhone, $invitePhoneList)){
            //判断是否还有剩余邀请码
            $residueNum = School::where('sch_invite_code', $inviteInfo->data->inviteCode)->value('sch_invite_code_residue');
            if($residueNum > 0){
                //进行绑定
//                $invite = new InviteCode();
//                $invite->invite_code = $inviteInfo->data->inviteCode;
//                $invite->teacher_phone =$inviteInfo->data->invitePhone;
//                $invite->unionID =$inviteInfo->data->unionID;
//                $invite->school_id =$schoolId;
//                $invite->create_time = date('Y-m-d H:i:s');
//                $res = $invite ->save();
                $res = Teacher::where('tc_unionID', $inviteInfo->data->unionID)->update(['tc_school'=>$schoolId]);
                School::where('sch_invite_code', $inviteInfo->data->inviteCode)->decrement('sch_invite_code_residue', 1);
                if($res){
                    return json_encode(['type' => 'bindResultSuccess', 'data' => ['message' => '绑定成功', 'status'=>3]]);
                }else{
                    return json_encode(['type' => 'bindResultxxx', 'data' => ['message' => '绑定失败，稍后再试', 'status'=>2]]);
                }
            }else{
                return json_encode(['type' => 'bindResultxx', 'data' => ['message' => '绑定失败,邀请码已经用完', 'status'=>2]]);
            }
        }
    }
    
    //测试redis 服务
    public function testRedis(){
        $key = 'name';
        $value = 'heh';
        $info = Redis::Set($key, $value);
        $values = Redis::get($key);
        return $values;
    }
}
