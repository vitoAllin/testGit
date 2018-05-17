<?php

namespace App\Http\Controllers\admin;

use App\Http\Model\AppUser;
use App\Http\Model\InviteCode;
use App\Http\Model\School;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class AppUserController extends Controller
{
    //登录时检查是否存在此用户
    public function checkAppUser(Request $request)
    {
        $msg = json_decode($request->check);
        $AppUserUnionIdArr = AppUser::getUnionIdList();
        $AppUserPhoneNumArr = AppUser::getPhoneNumList();
        //判断是那种登录方式
        if(isset($msg-> data -> unionID)){
            //微信登录验证
            $isExistAppUser = in_array($msg-> data -> unionID, $AppUserUnionIdArr);
            //如果微信账号不存在则直接创建用户
            if(!$isExistAppUser){
                $appUserInfo = [];
                $appUserInfo['app_user_unionID'] = $msg-> data -> unionID;
                $appUserInfo['app_user_wechatname'] = $msg-> data -> weChatName;
                $appUserInfo['app_user_logtimes'] = 1;
                $appUserInfo['app_user_iswechat'] = 1;
                $appUserInfo['app_user_createtime'] = date('Y-m-d H:i:s');
				$appUserInfo['app_user_headUrl'] = $msg-> data -> headUrl;
                //dd($appUserInfo);
                appUser::create($appUserInfo);
            }
            return (json_encode(['type' => 'checkWeChatRes', 'data' => ['result' =>true]]));
        }elseif(isset($msg-> data ->phoneNum)){
            if(!is_null($msg-> data ->phoneNum)){
                //手机号登录验证
                $isExistAppUser = in_array($msg-> data ->phoneNum, $AppUserPhoneNumArr);
                $checkRes = $isExistAppUser ? true : false ;
                return (json_encode(['type' => 'checkAppUserRes', 'data' => ['result' => $checkRes]]));
            }else{
				return (json_encode(['type' => 'checkAppUserRes', 'data' => ['result' => '手机号没有填写']]));
			}
        }
    }

    //用户登录
    public function loginAppUser(Request $request)
    {
        $userInfo = AppUser::weChatToUser($request->unionID);
        //dd($request->unionID, $userInfo);
        $msg = json_decode($request->login);
        //判断是手机登录还是微信登录
        if(isset($msg-> data -> phoneNum)){
            //判断用户账号密码是否正确
            $userInfo = AppUser::phoneToUser($msg-> data ->phoneNum);
            //验证用户密码是否正确
            if(!(Crypt::decrypt($userInfo->app_user_pass) == $msg-> data -> pass)){
                //验证失败 返回失败信息
                //return (json_encode(['type' => 'LoginAppUserRes', 'data' => ['result' => '用户密码错误']], JSON_UNESCAPED_UNICODE));
                return (json_encode(['type' => 'LoginAppUserRes', 'data' => ['result' => false, 'data'=>'用户密码错误']], JSON_UNESCAPED_UNICODE));
            }else{
                //验证成功 返回用户信息
                unset($userInfo->app_user_pass);
                //return (json_encode(['type' => 'LoginAppUserRes', 'data' => ['result' => $userInfo]]));
                return (json_encode(['type' => 'LoginAppUserRes', 'data' => ['result' => true, 'data'=>$userInfo]]));
            }
        }elseif(isset($msg-> data ->unionID)){
            //微信登录直接返回用户信息
            $userInfo = AppUser::weChatToUser($msg-> data -> unionID);
			//return (json_encode(['type' => 'LoginWeChatRes', 'data' => ['isBindPhone'=>$userInfo->app_user_phone]]));
            unset($userInfo->app_user_pass);
            //判断是否绑定过手机
            if($userInfo->app_user_phone){
                return (json_encode(['type' => 'LoginWeChatRes', 'data' => ['isBindPhone'=>true]]));
            }else{
                return (json_encode(['type' => 'LoginWeChatRes', 'data' => ['isBindPhone'=>false]]));
            }
        }
    }

    //注册创建用户
    public function createAppUser(Request $request)
    {
        $msg = json_decode($request->register);
        //判断用户是否已经注册
        $AppUserPhoneNumArr = AppUser::getPhoneNumList();
        if(isset($msg -> data ->phoneNum)){
            $isExistAppUser = in_array($msg -> data ->phoneNum, $AppUserPhoneNumArr);
            $checkRes = $isExistAppUser ? true : false ;
            if($checkRes){
                return (json_encode(['type' => 'registerRes', 'data' => ['result' => 'false']]));
            }
        }
        /*$rules = [
            'phoneNum' => 'required|regex:/^1[345789][0-9]{9}$/',
            'pass' => 'required|min:8|max:12'
        ];
        $message = [
            'phoneNum.required'=>'手机号不能为空！',
            'pass.required'=>'用户密码不能为空！',
            'phoneNum.regex'=>'用户手机格式不正确！',
            'pass.min'=>'密码输入为8-12位',
            'pass.max'=>'密码输入为8-12位',
        ];
        $validator = Validator::make(msg ,$rules,$message);*/
        //if($validator->passes()){
            //验证成功 创建用户信息
            $appUserInfo = [];
            $appUserInfo['app_user_phone'] = $msg->data -> phoneNum;
            $appUserInfo['app_user_pass'] = Crypt::encrypt( $msg->data -> pass);
            $appUserInfo['app_user_logtimes'] = 1;
            $appUserInfo['app_user_iswechat'] = 0;
            $appUserInfo['app_user_createtime'] = date('Y-m-d H:i:s');
            //dd($appUserInfo);
            $createRes = appUser::create($appUserInfo);
            return (json_encode(['type' => 'registerRes', 'data' => ['result' => 'true']]));
        //}else{
            //验证失败 返回失败信息
            //var_dump($validator->errors()->all());
           // return (json_encode(['type' => 'createAppUserRes', 'data' => ['result' => $validator->errors()->all()]], JSON_UNESCAPED_UNICODE));
        //}
    }

    //微信 手机绑定
    public function weChatAndPhone(Request $request)
    {
        $msg = json_decode($request->bindPhone);
		//return (json_encode(['type' => 'bindRes', 'data' => ['result' => 'true']]));
        //判断改手机号是否存在
        $AppUserPhoneNumArr = AppUser::getPhoneNumList();
        $isExistAppUser = in_array($msg->data->phoneNum, $AppUserPhoneNumArr);
        if(!$isExistAppUser){
            $user = AppUser::weChatToUser($msg->data->unionID);
            //如果不存在直接进行进行绑定
            $user->app_user_phone = $msg->data->phoneNum;
            $user->app_user_pass =  Crypt::encrypt($msg->data->pass);
            $user->save();
            return (json_encode(['type' => 'bindRes', 'data' => ['isNewPhoneNum' => 'true', 'changePass'=>'true']]));
        }else{
            //如果存在该手机号 判断是否已经和微信绑定过
            //把微信的信息作废
            $users = AppUser::where('app_user_unionID', $msg->data->unionID)->first();
            if(!$users->app_user_phone){
                $users->app_user_effective = 0;
            }
            $users->save();
			
			$user = AppUser::where('app_user_phone', $msg->data->phoneNum)->first();
            $user->app_user_unionID = $msg->data->unionID;
            $user->app_user_wechatname = $msg->data->weChatName;
            $user->app_user_headUrl = $msg->data->headUrl;
            //判断用户密码是否做了更改
            if($msg->data->pass != Crypt::decrypt($user->app_user_pass)){
                $user->app_user_pass = Crypt::encrypt($msg->data->pass);
                $user->save();
                return (json_encode(['type' => 'bindRes', 'data' => ['isNewPhoneNum' => 'false', 'changePass'=>'true']]));
            }else{
                $user->save();
                return (json_encode(['type' => 'bindRes', 'data' => ['isNewPhoneNum' => 'false', 'changePass'=>'false']]));
            }
        }
    }

    //APP用户列表
    public function show()
    {
        $AppUserData = AppUser::where('app_user_effective', 1)->orderBy('app_user_id','desc')->get();
        return view('admin.appUser.index',compact('AppUserData'));
    }

    //APP邀请码绑定
    public function bindInviteCode(Request $request)
    {
        $inviteInfo = json_decode($request->bind);
        $inviteCodeList = School::lists('sch_invite_code')->toArray();
        $invitePhoneList = InviteCode::lists('appUser_phone')->toArray();
        $schoolId = School::where('sch_invite_code', $inviteInfo->data->inviteCode)->value('sch_id');

        //判断邀请码是否有效
        if(!in_array($inviteInfo->data->inviteCode, $inviteCodeList)){
            return json_encode(['type' => 'bindResultFail', 'data' => ['message' => '绑定失败，输入的邀请码不正确', 'status'=>1]]);
        }

        //邀请码有效的情况下 判断情况
        //1.用户已经是注册用户并且自行绑定了
        $isInvited = appUser::where('app_user_unionID', $inviteInfo->data->unionID)->value('appUser_school');
        if($isInvited){
            return json_encode(['type' => 'bindResultHasBind', 'data' => ['message' => '您已经绑定']]);
        }

        //2 后台已经绑定了 但是用户没有绑定
        if(in_array($inviteInfo->data->invitePhone, $invitePhoneList) && !(InviteCode::where('appUser_phone', $inviteInfo->data->invitePhone)->value('unionID'))){
            InviteCode::where('appUser_phone',  $inviteInfo->data->invitePhone)->delete();
            appUser::where('app_user_unionID', $inviteInfo->data->unionID)->update(['appUser_school'=>$schoolId]);
            School::where('sch_invite_code', $inviteInfo->data->inviteCode)->decrement('sch_invite_code_residue', 1);
            return json_encode(['type' => 'bindResultOver', 'data' => ['message' => '绑定成功']]);
        }

        //3 用户自己做绑定，后台没有提前绑定
        if(!in_array($inviteInfo->data->invitePhone, $invitePhoneList)){
            //判断是否还有剩余邀请码
            $residueNum = School::where('sch_invite_code', $inviteInfo->data->inviteCode)->value('sch_invite_code_residue');
            if($residueNum > 0){
                $res = appUser::where('app_user_unionID', $inviteInfo->data->unionID)->update(['appUser_school'=>$schoolId]);
                School::where('sch_invite_code', $inviteInfo->data->inviteCode)->decrement('sch_invite_code_residue', 1);
                if($res){
                    return json_encode(['type' => 'bindResultFail', 'data' => ['message' => '绑定成功', 'status'=>3]]);
                }else{
                    return json_encode(['type' => 'bindResultFail', 'data' => ['message' => '绑定失败，稍后再试', 'status'=>2]]);
                }
            }else{
                return json_encode(['type' => 'bindResultFail', 'data' => ['message' => '绑定失败,邀请码已经用完', 'status'=>4]]);
            }
        }
    }

    //后台修改用户信息页面
    public function editShowAppUser($appUserId)
    {
        //获取用户信息
        $appUserInfo = AppUser::where('app_user_id', $appUserId)->first();
        return view('admin.appUser.userInfo')->with(['appUserInfo'=>$appUserInfo]);
    }

    //后台修改用户信息
    public function editAppUser(Request $request)
    {
        var_dump($request->all());
        $user = AppUser::find($request->appUserId);
        $rules = [
          'appUserPhone' => 'required|regex:/^1[345789][0-9]{9}$/',
          'appUserPass' => 'required|min:8|max:12'
        ];
        $message = [
          'appUserPhone.required'=>'手机号不能为空！',
          'appUserPass.required'=>'用户密码不能为空！',
          'appUserPhone.regex'=>'用户手机格式不正确！',
          'appUserPass.min'=>'密码输入为8-12位',
          'appUserPass.max'=>'密码输入为8-12位',
        ];
        $validator = Validator::make($request->all() ,$rules,$message);
        //dd($validator->passes(), $validator->errors()->all());
        if($validator->passes()){
            $user->app_user_phone = $request->appUserPhone;
            $user->app_user_pass = Crypt::encrypt($request->appUserPass);
            $user->save();
            return redirect()->back()->with(['msg'=>'修改成功']);
        }else{
            return redirect()->back()->withErrors($validator);
        }
    }

    //APP用户忘记密码/或者用户修改密码
    public function forgetPass(Request $request)
    {
//        $jsonTemplate  = '{"type":"forgetPass","data":{"phoneNum":"13203330111","pass":"123456789012"}}';
//        //'{"msg":{"type":"forgetPass","data":{"phoneNum":"13303330111","pass":"12345678"}}}';
//        $jToObj = json_decode($jsonTemplate);
//        var_dump($jToObj['data']);
        //查找到用户
        $msg = json_decode($request->forgetPass);
        $user = AppUser::phoneToUser($msg -> data -> phoneNum);
        //修改密码
        $user->app_user_pass =  Crypt::encrypt($msg->data->pass);
        $user->save();
        return json_encode(['type' => 'forgetPassRes', 'data' => ['result' => 'true']]);
    }

    //测试
    public function test(Request $request)
    {
        dd($request->all());
        return view('admin.test');
    }

    //发送请求返回用户信息
    public function getUserInfo(Request $request)
    {
        $msg = json_decode($request->getUserInfo);
        if($msg->data->phoneNum){
            $user = AppUser::phoneToUser($msg -> data -> phoneNum);
        }elseif(!$msg->data->phoneNum && $msg->data->unionID){
            $user = AppUser::weChatToUser($msg->data->unionID);
        }
        $user->app_user_pass = Crypt::decrypt($user->app_user_pass);
        return json_encode(['type' => 'getUserInfoRes', 'data' => ['result' => $user]]);
    }
}
