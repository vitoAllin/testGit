<?php

namespace App\Http\Controllers\Admin;

use App\Http\Model\Authorization;
use App\Http\Model\Cooperation;
use App\Http\Model\User;
use Illuminate\Http\Request;
use App\Http\Model\Managers;
use App\Http\Model\School;
use App\Http\Model\SchoolManagerRelation;
use App\Http\Requests;
use Illuminate\Support\Facades\Session;
use DB;
use Auth;
use App\Role;

class SchoolController extends CommonController
{
    //学校列表
    public function index(){
        //获取订单的信息
        $order = Authorization::select('order_id', 'order_buyer')->get();
        //更新之后的提示
        $updateMsg = Session::get('updateMsg');
        //获取学校数据
        $school = School::join('order', 'school.sch_order_oid', '=', 'order.order_id')
                        ->join('schandmgr', 'schandmgr.school_id', '=', 'school.sch_id')
                        ->join('manager', 'manager.mgr_id' ,'=', 'schandmgr.manager_id')
                        ->leftJoin('order_son', 'school.sch_order_son_oid', '=', 'order_son.order_id')
                        ->select('school.sch_id as id', 'school.sch_name as name', 'school.sch_code as schoolCode',
                            'school.sch_order_status as status' ,'school.sch_invite_code as inviteCode',
                            'school.sch_invite_code_residue as residueNum',
                            'school.sch_order_start as startTime', 'school.sch_order_stop as overTime',
                            'school.sch_invite_code_num as num', 'order.order_buyer as buyer',
                            'order.order_location as location', 'order_son.order_location as sonLocation', 'schandmgr.manager_id as managerId',
                            'manager.mgr_name as managerName', 'manager.mgr_phone as managerPhone')->orderBy('school.sch_id', 'desc')->paginate(10);
        return view('admin.authorization.school')->with(['allSchoolInfo'=>$school, 'allOrderInfo'=>$order, 'schoolUpdateMsg'=>$updateMsg]);
    }

    //合作商管理员 学校列表
    public function cooperationSchool(){
        //检查用HU
        $user = Auth::user();
        $userOid = $user->user_oid;
        //获取订单的信息
        $order = Cooperation::where('order_pid', $userOid)->select('order_id', 'order_buyer')->first();
        //更新之后的提示
        $updateMsg = Session::get('updateMsg');
        //获取学校数据
        //DB::enableQueryLog();
        $school = School::where('school.sch_order_oid', $userOid)
            ->join('order', 'school.sch_order_oid', '=', 'order.order_id')
            ->join('schandmgr', 'schandmgr.school_id', '=', 'school.sch_id')
            ->leftJoin('order_son', 'school.sch_order_son_oid', '=', 'order_son.order_id')
            ->join('manager', 'manager.mgr_id' ,'=', 'schandmgr.manager_id')
            ->select('school.sch_id as id', 'school.sch_name as name', 'school.sch_code as schoolCode',
                'school.sch_order_status as status' ,'school.sch_invite_code as inviteCode',
                'school.sch_invite_code_residue as residueNum',
                'school.sch_order_start as startTime', 'school.sch_order_stop as overTime',
                'school.sch_invite_code_num as num', 'order.order_buyer as buyer', 'order_son.order_buyer as buyerSon',
                'order.order_location as location', 'order_son.order_location as sonLocation', 'schandmgr.manager_id as managerId',
                'manager.mgr_name as managerName', 'manager.mgr_phone as managerPhone')
                ->orderBy('school.sch_id', 'desc')->paginate(10);
        //dd(DB::getQueryLog());
        return view('admin.authcooperation.school')->with(['allSchoolInfo'=>$school, 'allOrderInfo'=>$order, 'schoolUpdateMsg'=>$updateMsg]);
    }

    //教育局管理员 学校列表
    public function educationSchool(){
        //判断教育管理员是超级管理员授权的还是合作商管理员授权的
        $user = Auth::user();
        $userOid = $user->user_oid ;
        //获取订单的信息
        if($user->user_flag == 1){
            //超级管理员 授权
            $order = Authorization::where('order_id',$userOid)->select('order_id', 'order_buyer')->first();
            //更新之后的提示
            $updateMsg = Session::get('updateMsg');
            //获取学校数据
            $school = School::where('school.sch_order_oid', $order->order_id)
                ->join('order','school.sch_order_oid', '=', 'order.order_id')
                ->leftJoin('order_son','school.sch_order_son_oid', '=', 'order_son.order_id')
                ->join('schandmgr', 'schandmgr.school_id', '=', 'school.sch_id')
                ->join('manager', 'manager.mgr_id' ,'=', 'schandmgr.manager_id')
                ->select('school.sch_id as id', 'school.sch_name as name', 'school.sch_code as schoolCode',
                    'school.sch_order_status as status' ,'school.sch_invite_code as inviteCode',
                    'school.sch_invite_code_residue as residueNum',
                    'school.sch_order_start as startTime', 'school.sch_order_stop as overTime',
                    'school.sch_invite_code_num as num', 'order.order_buyer as buyer',
                    'order.order_location as location', 'order_son.order_location as sonLocation', 'schandmgr.manager_id as managerId',
                    'manager.mgr_name as managerName', 'manager.mgr_phone as managerPhone')->orderBy('school.sch_id', 'desc')->paginate(10);
            return view('admin.autheducation.school')->with(['allSchoolInfo'=>$school, 'allOrderInfo'=>$order, 'schoolUpdateMsg'=>$updateMsg]);
        }else if($user->user_flag == 2){
            //合作商管理员 授权
            $order = Cooperation::where('order_id',$userOid)->select('order_id', 'order_buyer')->first();
//            dd($order);
            //更新之后的提示
            $updateMsg = Session::get('updateMsg');
            //获取学校数据
            $school = School::where('school.sch_order_son_oid', $order->order_id)->join('order_son','school.sch_order_son_oid', '=', 'order_son.order_id')
                ->join('schandmgr', 'schandmgr.school_id', '=', 'school.sch_id')
                ->join('manager', 'manager.mgr_id' ,'=', 'schandmgr.manager_id')
                ->select('school.sch_id as id', 'school.sch_name as name', 'school.sch_code as schoolCode',
                    'school.sch_order_status as status' ,'school.sch_invite_code as inviteCode',
                    'school.sch_invite_code_residue as residueNum',
                    'school.sch_order_start as startTime', 'school.sch_order_stop as overTime',
                    'school.sch_invite_code_num as num', 'order_son.order_buyer as buyer',
                    'order_son.order_location as location', 'schandmgr.manager_id as managerId',
                    'manager.mgr_name as managerName', 'manager.mgr_phone as managerPhone')->orderBy('school.sch_id', 'desc')->paginate(10);
            return view('admin.autheducation.school')->with(['allSchoolInfo'=>$school, 'allOrderInfo'=>$order, 'schoolUpdateMsg'=>$updateMsg]);
        }
    }

    //学校绑定页面
    public function binding(Request $request){
        //已经绑定的学校
        $hasBindSchool =School::join('order', 'school.sch_order_oid', '=', 'order.order_id')
                        ->join('schandmgr', 'schandmgr.school_id', '=', 'school.sch_id')
                        ->join('manager', 'manager.mgr_id' ,'=', 'schandmgr.manager_id')
                        ->where('order.order_id', $request -> authId)
                        ->select('school.sch_id as id', 'school.sch_name as name', 'school.sch_code as schoolCode',
                            'school.sch_order_status as status' ,'school.sch_invite_code as inviteCode',
                            'school.sch_order_start as startTime', 'school.sch_order_stop as overTime',
                            'school.sch_invite_code_num as num', 'school.sch_create_time as createTime',
                            'order.order_location as location', 'schandmgr.manager_id as managerId',
                            'manager.mgr_name as managerName', 'manager.mgr_phone as managerPhone')->orderBy('school.sch_id', 'desc')->get();
        //学校数量
        $schoolBindNum = School::where('sch_order_oid', $request -> authId)->count();
        //超级管理员绑定学校页面
        $authFlag = 4;
        //订单（授权）id
        return view('admin.school.schbinding')->with(['oid'=>$request -> authId, 'authNumber'=> $request->authNumber, 'orderCode'=>$request->orderCode, 'orderLife'=>$request-> orderLife, 'buyer'=>$request->buyer, 'residueNum'=>$request->residueNum,'hasBind'=>$hasBindSchool, 'startTime'=>substr($request-> orderLife, 0, 10), 'overTime'=>substr($request->orderLife, 13), 'schoolBindNum'=>$schoolBindNum, 'authFlag'=>$authFlag]);
    }

    //合作商学校绑定页面
    public function cooperationbinding(Request $request){
        //已经绑定的学校
        $hasBindSchool =School::join('order_son', 'school.sch_order_son_oid', '=', 'order_son.order_id')
            ->join('schandmgr', 'schandmgr.school_id', '=', 'school.sch_id')
            ->join('manager', 'manager.mgr_id' ,'=', 'schandmgr.manager_id')
            ->where('order_son.order_id', $request -> authId)
            ->select('school.sch_id as id', 'school.sch_name as name', 'school.sch_code as schoolCode',
                'school.sch_order_status as status' ,'school.sch_invite_code as inviteCode',
                'school.sch_order_start as startTime', 'school.sch_order_stop as overTime',
                'school.sch_invite_code_num as num', 'school.sch_create_time as createTime',
                'order_son.order_location as location', 'schandmgr.manager_id as managerId',
                'manager.mgr_name as managerName', 'manager.mgr_phone as managerPhone')->orderBy('school.sch_id', 'desc')->get();
        //学校数量
        $schoolBindNum = School::where('sch_order_son_oid', $request -> authId)->count();
        //订单（授权）id
        //判断是超级管理员绑定的还是合作商管理员绑定的
        if(UsersController::seeRoles('educationAdmin')){
            $authFlag = 2;
        }elseif(UsersController::seeRoles('cooperationAdmin')){
            $authFlag = 3;
        }elseif(UsersController::seeRoles('admin')){
            $authFlag = 1;
        }
        return view('admin.school.schbinding')->with(['oid'=>$request -> authId, 'pid'=>$request -> authPid,'authNumber'=> $request->authNumber, 'orderCode'=>$request->orderCode, 'orderLife'=>$request-> orderLife, 'buyer'=>$request->buyer, 'residueNum'=>$request->residueNum,'hasBind'=>$hasBindSchool, 'startTime'=>substr($request-> orderLife, 0, 10), 'overTime'=>substr($request->orderLife, 13), 'schoolBindNum'=>$schoolBindNum, 'authProperty'=>'cooperation', 'authFlag'=>$authFlag]);
    }

    //学校和管理员绑定
    public function addSchoolAndManager(Request $request){
//        dd($request->all());
        $schoolObj = json_decode($request->jsonData, true);
        //正常订单 账号个数满足
        $residueNum = Authorization::where('order_id', $request->authId)->value('order_number_residue');
        if( $residueNum - $request->codeNum < 0) {
            return json_encode(['addMsg' => '2']);
        }
        foreach($schoolObj as $key => $value){
            //实例 学校表
            $school = new School();
            //实例 管理员表
            $manager = new Managers();
            //实例关系表
            $schRelationMgr = new SchoolManagerRelation();
            //添加到学校表
            $school->sch_name = $value['schoolName'];
            //自动生成学校code
            $nowTime = date('Y-m-d H:i:s');

            $schoolCode =  $school->getSchoolCode();
            $school->sch_code = $schoolCode;
            $school->sch_invite_code_num = $value['accountNumber'];
            $school->sch_invite_code_residue = $value['accountNumber'];
            $school->sch_order_start = $value['act_start_time'];
            $school->sch_order_stop = $value['act_stop_time'];
            $school->sch_create_time = $nowTime;
            $school->sch_order_oid = $request->authId;
            $school->sch_order_status = 1;
            $school->sch_invite_code =  $school->invitationCode();
            $school->save();
            $schoolId = $school->sch_id;
            //将订单表里面的账号个数减掉
            $residueNum -= $value['accountNumber'];

            //添加到管理员表
            $manager->mgr_name = $value['managerName'];
            $manager->mgr_phone = $value['managerPhone'];
            $manager->mgr_create_time = $nowTime;
            $manager->save();
            $managerId = $manager->mgr_id;

            //管理员和学校关系表
            $schRelationMgr->school_id = $schoolId;
            $schRelationMgr->manager_id = $managerId;
            $schRelationMgr->save();

            //将学校的管理员插入到user表
            $this->insertUser($value['managerName'],$value['managerPhone'],$request->authId, 1, $schoolId);
        }
        //更新订单所剩账号个数
        Authorization::where('order_id',$request->authId)->update(['order_number_residue'=>$residueNum]);
        return json_encode(['addMsg' => '1']);
    }

    //合作商 学校和管理员绑定
    public function CooperationAddSchoolAndManager(Request $request){
//        var_dump('合作商');
//        dd($request->all());
        $schoolObj = json_decode($request->jsonData, true);
        //子订单（合作商订单） 账号个数满足
        $residueNum = Cooperation::where('order_id', $request->authId)->value('order_number_residue');
        if( $residueNum - $request->codeNum < 0) {
            return json_encode(['addMsg' => '2']);
        }

        foreach($schoolObj as $key => $value){
            //实例 学校表
            $school = new School();
            //实例 管理员表
            $manager = new Managers();
            //实例关系表
            $schRelationMgr = new SchoolManagerRelation();
            //添加到学校表
            $school->sch_name = $value['schoolName'];
            //自动生成学校code
            $nowTime = date('Y-m-d H:i:s');
            $schoolCode = $school->getSchoolCode();
            //学校ID
            $school->sch_code = $schoolCode;
            $school->sch_invite_code_num = $value['accountNumber'];
            $school->sch_invite_code_residue = $value['accountNumber'];
            $school->sch_order_start = $value['act_start_time'];
            $school->sch_order_stop = $value['act_stop_time'];
            $school->sch_create_time = $nowTime;
            $school->sch_order_oid = $request->authPid;
            $school->sch_order_son_oid = $request->authId;
            $school->sch_order_status = 1;
            $school->sch_invite_code =  $school->invitationCode();
            $school->save();
            $schoolId = $school->sch_id;
            //将订单表里面的账号个数减掉
            $residueNum -= $value['accountNumber'];

            //添加到管理员表
            $manager->mgr_name = $value['managerName'];
            $manager->mgr_phone = $value['managerPhone'];
            $manager->mgr_create_time = $nowTime;
            $manager->save();
            $managerId = $manager->mgr_id;

            //管理员和学校关系表
            $schRelationMgr->school_id = $schoolId;
            $schRelationMgr->manager_id = $managerId;
            $schRelationMgr->save();

            $this->insertUser($value['managerName'],$value['managerPhone'],$request->authId, 2, $schoolId);
        }
        //更新订单所剩账号个数
        Cooperation::where('order_id',$request->authId)->update(['order_number_residue'=>$residueNum]);
        return json_encode(['addMsg' => '1']);
    }


    //生成邀请码
    public function invitationCode(){
        //rand 6位随机数
        $randCode = $this->GetRandDigit(6);
        $inviteCode = $randCode;
        return $inviteCode;
    }

    //生成随机数
    public function GetRandDigit($length){
        $str='0123456789';
        $len=strlen($str)-1;
        $randStr='';
        for($i=0;$i<$length;$i++){
            $num=mt_rand(0,$len);
            $randStr .= $str[$num];
        }
        //判断邀请码是否已经存在
        $inviteCodeList = School::lists('sch_invite_code')->toArray();
        if(!in_array($randStr,$inviteCodeList)){
            return $randStr;
        }else{
            $this->GetRandDigit(6);
        }
    }

    //编辑学校 管理员 页面
    public function show(Request $request){
        $schRes = School::join('schandmgr', 'school.sch_id', '=', 'schandmgr.school_id')
                        ->join('manager', 'manager.mgr_id', '=', 'schandmgr.manager_id')
                        ->where('sch_id', $request->schoolId)
                        ->select('school.*', 'manager.*')->first();
        return view('admin.school.show')->with(['schAndMgr'=>$schRes, 'currentPage'=>$request->currentPage]);
    }

    //编辑学校 合作商 页面
    public function cooperationShow(Request $request){
        $schRes = School::join('schandmgr', 'school.sch_id', '=', 'schandmgr.school_id')
            ->join('manager', 'manager.mgr_id', '=', 'schandmgr.manager_id')
            ->where('sch_id', $request->schoolId)
            ->select('school.*', 'manager.*')->first();
        //判断是否是授权商/后台绑定的学校
        return view('admin.school.show')->with(['schAndMgr'=>$schRes, 'currentPage'=>$request->currentPage, 'schoolProperty'=>'cooperation']);
    }

    //编辑学校 教育局 页面
    public function educationShow(Request $request){
        $schRes = School::join('schandmgr', 'school.sch_id', '=', 'schandmgr.school_id')
            ->join('manager', 'manager.mgr_id', '=', 'schandmgr.manager_id')
            ->where('sch_id', $request->schoolId)
            ->select('school.*', 'manager.*')->first();
        return view('admin.school.show')->with(['schAndMgr'=>$schRes, 'currentPage'=>$request->currentPage, 'schoolProperty'=>'education']);
    }

    //学校 管理员修改操作
    public function update(Request $request){
        //判断修改的学校是总订单表里的还是合作商订单表里的
        $schSonOid = School::where('school.sch_id', $request->schoolId)->value('sch_order_son_oid');
//        dd($schSonOid);
        if(!$schSonOid){
            //总订单列表
            //修改账号个数时做判断
            $residueNum = Authorization::join('school' , 'school.sch_order_oid', '=', 'order_id')
                                       ->where('school.sch_id', $request->schoolId)->select('order_id', 'order_number','order_number_residue', 'sch_invite_code_num', 'sch_invite_code_residue')
                                       ->first();
            $changeNum = $request->schoolNum - ($residueNum->sch_invite_code_num);
            //判断修改的个数是否大于订单的个数
            if($request->schoolNum <= $residueNum->order_number){
                if( ($residueNum->order_number_residue) -  $changeNum < 0) {
                    return back()->with(['updateMsg' => '2']);
                }else{
                    //判断更新个数后 修改学校的剩余账号个数
                    if($residueNum->sch_invite_code_residue + $changeNum > 0){
                        School::where('sch_id', $request->schoolId)->increment('sch_invite_code_residue', $changeNum);
                    }else{
                        School::where('sch_id', $request->schoolId)->update(['sch_invite_code_residue'=>0]);
                    }
                }
            }else{
                return back()->with(['updateMsg' => '2']);
            }
            //更新订单表的剩余数量
            Authorization::where('order_id', $residueNum->order_id)->decrement('order_number_residue', $changeNum);
        }else{
            //合作商订单
            $residueNum = Cooperation::join('school' , 'school.sch_order_son_oid', '=', 'order_id')
                ->where('school.sch_id', $request->schoolId)->select('order_id', 'order_number','order_number_residue', 'sch_invite_code_num', 'sch_invite_code_residue')
                ->first();
            $changeNum = $request->schoolNum - ($residueNum->sch_invite_code_num);
            //判断修改的个数是否大于订单的个数
            if($request->schoolNum <= $residueNum->order_number){
                if( ($residueNum->order_number_residue) -  $changeNum < 0) {
                    return back()->with(['updateMsg' => '2']);
                }else{
                    //判断更新个数后 修改学校的剩余账号个数
                    if($residueNum->sch_invite_code_residue + $changeNum > 0){
                        School::where('sch_id', $request->schoolId)->increment('sch_invite_code_residue', $changeNum);
                    }else{
                        School::where('sch_id', $request->schoolId)->update(['sch_invite_code_residue'=>0]);
                    }
                }
            }else{
                return back()->with(['updateMsg' => '2']);
            }
            //更新订单表的剩余数量
            Cooperation::where('order_id', $residueNum->order_id)->decrement('order_number_residue', $changeNum);
        }
        //更新学校其他的字段
        $schRes = School::where('sch_id', $request->schoolId)->update(['sch_name'=>$request->schoolName, 'sch_invite_code_num'=>$request->schoolNum, 'sch_order_start'=>$request->act_start_time, 'sch_order_stop'=>$request->act_stop_time]);

        //获取管理员的id
        $managerId = SchoolManagerRelation::where('school_id', $request->schoolId)->value('manager_id');
        //修改管理员信息
        $mgrRes = Managers::where('manager.mgr_id', $managerId)->update(['mgr_name'=>$request->managerName,
'mgr_phone'=>$request->managerPhone]);
//        var_dump($schRes, $mgrRes);
        return redirect('admin/authorization/school?page='.$request->currentPage)->with(['updateMsg'=>1]);
    }

    //学校 合作商管理员修改操作
    public function CooperationUpdate(Request $request){
        //判断修改的学校是否是合作商分配的
        $residueNum = Cooperation::join('school' , 'school.sch_order_son_oid', '=', 'order_id')
            ->where('school.sch_id', $request->schoolId)->select('order_id', 'order_number','order_number_residue', 'sch_invite_code_num', 'sch_invite_code_residue')
            ->first();
        //var_dump($residueNum);
        $changeNum = $request->schoolNum - ($residueNum->sch_invite_code_num);
//        var_dump($changeNum);
        //判断修改的个数是否大于订单的个数
        if($request->schoolNum <= $residueNum->order_number){
            if( ($residueNum->order_number_residue) -  $changeNum < 0) {
                return back()->with(['updateMsg' => '2']);
            }else{
                //判断更新个数后 修改学校的剩余账号个数
                if($residueNum->sch_invite_code_residue + $changeNum > 0){
                    School::where('sch_id', $request->schoolId)->increment('sch_invite_code_residue', $changeNum);
                }else{
                    School::where('sch_id', $request->schoolId)->update(['sch_invite_code_residue'=>0]);
                }
            }
        }else{
            return back()->with(['updateMsg' => '2']);
        }

        //更新订单表的剩余数量
        Cooperation::where('order_id', $residueNum->order_id)->decrement('order_number_residue', $changeNum);
        //更新学校其他的字段
        $schRes = School::where('sch_id', $request->schoolId)->update(['sch_name'=>$request->schoolName, 'sch_invite_code_num'=>$request->schoolNum, 'sch_order_start'=>$request->act_start_time, 'sch_order_stop'=>$request->act_stop_time]);

        //获取管理员的id
        $managerId = SchoolManagerRelation::where('school_id', $request->schoolId)->value('manager_id');
        //修改管理员信息
        $mgrRes = Managers::where('manager.mgr_id', $managerId)->update(['mgr_name'=>$request->managerName,
            'mgr_phone'=>$request->managerPhone]);
            return redirect('admin/authcooperation/school?page='.$request->currentPage)->with(['updateMsg'=>1]);
    }

    //学校 教育局管理员修改操作
    public function EducationUpdate(Request $request){
        //判断是合作商授权的教育局管理员还是后台授权的教育局管理员
        $user = Auth::user();
        //获取订单的信息
        if($user->user_flag == 1){
            $residueNum = Authorization::join('school' , 'school.sch_order_oid', '=', 'order_id')
                ->where('school.sch_id', $request->schoolId)->select('order_id', 'order_number','order_number_residue', 'sch_invite_code_num', 'sch_invite_code_residue')
                ->first();
        }else{
            $residueNum = Cooperation::join('school' , 'school.sch_order_son_oid', '=', 'order_id')
                ->where('school.sch_id', $request->schoolId)->select('order_id', 'order_number','order_number_residue', 'sch_invite_code_num', 'sch_invite_code_residue')
                ->first();
        }
//        dd($residueNum);
        //修改账号个数时做判断

        $changeNum = $request->schoolNum - ($residueNum->sch_invite_code_num);
//        var_dump($changeNum);
        //判断修改的个数是否大于订单的个数
        if($request->schoolNum <= $residueNum->order_number){
            if( ($residueNum->order_number_residue) -  $changeNum < 0) {
                return back()->with(['updateMsg' => '2']);
            }else{
                //判断更新个数后 修改学校的剩余账号个数
                if($residueNum->sch_invite_code_residue + $changeNum > 0){
                    School::where('sch_id', $request->schoolId)->increment('sch_invite_code_residue', $changeNum);
                }else{
                    School::where('sch_id', $request->schoolId)->update(['sch_invite_code_residue'=>0]);
                }
            }
        }else{
            return back()->with(['updateMsg' => '2']);
        }

        //更新订单表的剩余数量
        if($user->user_flag == 1){
            Authorization::where('order_id', $residueNum->order_id)->decrement('order_number_residue', $changeNum);
        }else{
            Cooperation::where('order_id', $residueNum->order_id)->decrement('order_number_residue', $changeNum);
        }

        //更新学校其他的字段
        $schRes = School::where('sch_id', $request->schoolId)->update(['sch_name'=>$request->schoolName, 'sch_invite_code_num'=>$request->schoolNum, 'sch_order_start'=>$request->act_start_time, 'sch_order_stop'=>$request->act_stop_time]);

        //获取管理员的id
        $managerId = SchoolManagerRelation::where('school_id', $request->schoolId)->value('manager_id');
        //修改管理员信息
        $mgrRes = Managers::where('manager.mgr_id', $managerId)->update(['mgr_name'=>$request->managerName,
            'mgr_phone'=>$request->managerPhone]);
//        var_dump($schRes, $mgrRes);
        return redirect('admin/autheducation/school?page='.$request->currentPage)->with(['updateMsg'=>1]);
    }

    //更改邀请码
    public function changeInviteCode(Request $request){
        $schoolId = $request->schoolId;
        //获得新的邀请码、
        $newInviteCode =  $this->invitationCode();
        School::where('sch_id', $schoolId)->update(['sch_invite_code'=>$newInviteCode]);
        return  json_encode(['newInviteCode' => $newInviteCode, 'schoolId'=>$schoolId]);
    }
    
    //搜索学校
    public function search(Request $request){
//        var_dump($request);
        //获取订单的信息
        $order = Authorization::select('order_id', 'order_buyer')->get();
        $updateMsg = Session::get('updateMsg');
        //获取学校数据
        $school = School::join('order', 'school.sch_order_oid', '=', 'order.order_id')
            ->join('schandmgr', 'schandmgr.school_id', '=', 'school.sch_id')
            ->join('manager', 'manager.mgr_id' ,'=', 'schandmgr.manager_id')
            ->select('school.sch_id as id', 'school.sch_name as name', 'school.sch_code as schoolCode',
                'school.sch_order_status as status' ,'school.sch_invite_code as inviteCode',
                'school.sch_order_start as startTime', 'school.sch_order_stop as overTime',
                'school.sch_invite_code_num as num', 'order.order_buyer as buyer',
                'order.order_location as location', 'schandmgr.manager_id as managerId',
                'manager.mgr_name as managerName', 'manager.mgr_phone as managerPhone')
            ->where('order.order_province', 'LIKE', '%'.$request->province.'%')
            ->where('order.order_city', 'LIKE', '%'.$request->city.'%')
            ->where('order.order_district', 'LIKE', '%'.$request->district.'%')
            ->where('order.order_buyer', 'LIKE', '%'.$request->buyer.'%')
            ->where('school.sch_name', 'LIKE', '%'.$request->schoolName.'%')
            ->where('order.order_create_time', '>', date('Y-m-d H:i:s', strtotime($request->act_start_time)))
            ->where('school.sch_order_start', '>', date('Y-m-d H:i:s', strtotime($request->act_stop_time)))
//            ->where('school.sch_order_stop', '>', date('Y-m-d H:i:s', strtotime($request->act_stop_time)))
//            ->where('order.order_begin_time', 'LIKE', '%'.$request->act_start_time.'%')
//            ->where('order.order_over_time', 'LIKE', '%'.$request->act_stop_time.'%')
            ->orderBy('school.sch_id', 'desc')
            ->paginate(10);
        return view('admin.authorization.school')->with(['allSchoolInfo'=>$school, 'allOrderInfo'=>$order, 'schoolUpdateMsg'=>$updateMsg, 'province'=>$request->province, 'city'=>$request->city, 'district'=>$request->district, 'buyer'=>$request->buyer, 'schoolName'=>$request->schoolName,
        'act_start_time'=>$request->act_start_time, 'act_stop_time'=>$request->act_stop_time]);
    }
    
    //学校管理员 进入user表
    public function insertUser($userName, $userPhone, $authId, $userFlag, $schoolId)
    {
        $user = new User();
        $user->user_name =$userName;
        $user->user_pass = encrypt(substr($userPhone, -6));
        $user->user_oid = $authId;
        $user->user_flag = $userFlag;
        $user->user_school_id = $schoolId;
        $user->user_class = '1';
        $user->save();
        //设置权限
        $roleId = Role::where('name', 'schoolAdmin')->value('id');
        $user->roles()->attach($roleId);
    }

}