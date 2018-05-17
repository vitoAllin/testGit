<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Http\Model\AppUserSatisfaction;
use App\Http\Model\Category;
use App\Http\Model\CourseList;
use App\Http\Model\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexController extends Controller
{
    //显示课件默认详情页
    public function index(Request $request)
    {
		//dd($request->all());
        $tableType = AppUserSatisfaction::getType();
        $courseInfo = CourseList::where('coulist_code', $request->couId)->first();
        if($request->status == 'true'){
            $couDesignExplain = $courseInfo->coulist_designExplain ?  $courseInfo->coulist_designExplain : '';
            $couDesignExplainArr = explode('<hr/>', $couDesignExplain);
            $courseInfo->couDesignExplainArr = $couDesignExplainArr;
        }
        return view('home.coursePage.course2')->with(['courseInfo'=>$courseInfo, 'appUserPhone'=>$request->appUserPhone, 'unionID'=>$request->unionId, 'tableType'=>$tableType, 'couId'=>$request->couId, 'status'=>$request->status]);
    }

    //获取课件详情页
     public function article(Request $request)
    {
        $msg = json_decode($request->coursePage);
        $couId = $msg->data->couId;

        //用户信息
        $appUserPhone = $msg->data->appUserPhone;
        $unionID = $msg->data->unionID;

        $courseInfo = CourseList::where('coulist_code', $couId)->first();
		if(!$courseInfo->coulist_designExplain){
            $status = 'false';
//			return (json_encode(['type' => 'coursePageRes', 'data' => ['url' => $_SERVER['HTTP_HOST']. '/home/course/default/']], JSON_UNESCAPED_SLASHES));
		}else{
            $status = 'true';
        }
        $url = '';
        if(!empty($appUserPhone) || !empty($unionID)){
            $url = $_SERVER['HTTP_HOST'].'/home/coursePage?couId='.$couId.'&appUserPhone='.$appUserPhone.'&unionId='.$unionID.'&status='.$status;
        }
        return (json_encode(['type' => 'coursePageRes', 'data' => ['url' => $url]],JSON_UNESCAPED_SLASHES));
    }
    
    //获取用户提交的课件意见反馈
    public function userFeedback (Request $request)
    {
//		dd($request->all());
        $coulistId = $request->input('couId', 0);
        $operateEasy = $request->input('operate_easy', 5);
        $designStyle = $request->input('design_style', 5);
        $useInClass = $request->input('use_in_class', 5);
        $content = trim($request->input('content', ''));
        $unionId = $request->input('unionId', 0);
        $userPhone = $request->input('userPhone', 0);
        //获取用户id
        $appUser = new AppUser();
        if ($unionId) {
            $userId = $appUser->getUserIdByUnionId($unionId);
        } elseif ($userPhone) {
            $userId = $appUser->getUserIdByPhone($userPhone);
        } else {
            return json_encode(['scode' => 1,'msg' => '手机号和unionId至少传一个']);
        }
        
        $satisfactionModel = new AppUserSatisfaction();
        $res = $satisfactionModel->addNew($userId, $coulistId, $operateEasy, $designStyle, $useInClass, $content);

        if ($res) {
            return json_encode(['scode' => 0,'msg' => '提交成功！']);
        } else {
            return  json_encode(['scode' => 1,'msg' => '提交失败！']);
        }
    }
    public function feedbacklist(Request $request)
    {
        $page = $request->input('page', 1);
        $params['startTime'] = $startTime = $request->input('startTime', '');
        $params['endTime'] = $endTime = $request->input('endTime', '');
        $pageSize = 30;
        $skip = ($page-1)*$pageSize;
        $whereSql = $bindData = array();
        if ($startTime) {
            $whereSql[] = " saus_createTime >= :startTime";
            $bindData[':startTime'] = $startTime;
        }
        if ($endTime) {
            $whereSql[] = " saus_createTime <= :endTime";
            $bindData[':endTime'] = $endTime;
        }
        if (empty($whereSql)) {
            $whereSql[] = " 1 = 1 ";
        }
        $whereSql = implode(' and ', $whereSql);
        $sql = "SELECT scaus.coulist_id as id,sccl.coulist_code ,sccl.coulist_title, count(1) as ct,count(DISTINCT(app_user_id)) as ctu,round(avg(saus_operate_easy), 1) avg1,round(avg(saus_design_style), 1) avg2,round(avg(saus_use_in_class), 1) avg3 
            FROM sc_app_user_satisfaction AS scaus
            LEFT JOIN sc_courselist AS sccl 
            ON scaus.coulist_id = sccl.coulist_id
            WHERE $whereSql
            GROUP BY scaus.coulist_id
            ORDER BY sccl.coulist_code
            LIMIT {$skip},{$pageSize}";
        $data = DB::select($sql, $bindData);
        $countSql = "SELECT count(1) as ct 
            FROM sc_app_user_satisfaction AS scaus
            LEFT JOIN sc_courselist AS sccl 
            ON scaus.coulist_id = sccl.coulist_id
            WHERE $whereSql
            GROUP BY scaus.coulist_id";
        $count = DB::selectOne($countSql, $bindData);
        $href = '/home/feedbackDetail?id=';
        foreach ($data as $k => &$v) {
            $v->avgAll = round(($v->avg1 + $v->avg2 + $v->avg3) / 3, 1);
            $v->hrefa = $href.$v->id;
            unset($v);
        }
        $paginator = null;
        if ($count) {
            $paginator = new LengthAwarePaginator($data, $count->ct, $pageSize, $page, ['path' => '/home/feedbacklist']);
        }
        return view('home.coursePage.feedbacklist',compact('data', 'paginator', 'params'));
    }

    public function feedbackTeacherlist(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = 30;
        $skip = ($page-1)*$pageSize;
        $sql = "SELECT saus.app_user_id,app_user_phone,app_user_wechatname,COUNT(DISTINCT saus.coulist_id) AS ctn, COUNT(1) AS ct
            FROM sc_app_user_satisfaction AS saus
            LEFT JOIN sc_appuser AS sa
            ON saus.app_user_id= sa.app_user_id
            GROUP BY saus.app_user_id
            LIMIT {$skip},{$pageSize}";
        $data = DB::select($sql);
        $countSql = "SELECT count(distinct(app_user_id)) ct FROM sc_app_user_satisfaction;";
        $count = DB::selectOne($countSql);
        foreach ($data as $k => &$v) {
            $v->avg = round($v->ct / $v->ctn, 1);
            unset($v);
        }
        $paginator = new LengthAwarePaginator($data, $count->ct, $pageSize, $page, ['path' => '/home/feedbackTeacherlist']);
        return view('home.coursePage.feedbackTeacherlist',compact('data', 'paginator'));
    }
    public function feedbackDetail(Request $request)
    {
        $page = $request->input('page', 1);
        $id = $request->input('id', 1);
        $pageSize = 30;
        $skip = ($page-1)*$pageSize;
        // dataA
        $sql = "SELECT sccl.coulist_code ,sccl.coulist_title,round(avg(saus_operate_easy), 1) avg1,round(avg(saus_design_style), 1) avg2,round(avg(saus_use_in_class), 1) avg3 
            FROM sc_app_user_satisfaction AS scaus
            LEFT JOIN sc_courselist AS sccl 
            ON scaus.coulist_id = sccl.coulist_id
            WHERE scaus.coulist_id = :id
            GROUP BY scaus.coulist_id";
        $dataA = DB::selectOne($sql, [':id' => $id]);
        $dataA->avgAll = round(($dataA->avg1 + $dataA->avg2 + $dataA->avg3)/3, 1);
        // data
        $sql = "SELECT saus_createTime,app_user_phone,app_user_wechatname,saus_operate_easy,saus_design_style,saus_use_in_class,saus_content
            FROM sc_app_user_satisfaction AS saus
            LEFT JOIN sc_appuser AS sa
            ON saus.app_user_id= sa.app_user_id
            WHERE coulist_id = :id
            LIMIT {$skip},{$pageSize}";
        $data = DB::select($sql, [':id' => $id]);
        $countSql = "SELECT count(1) AS ct
            FROM sc_app_user_satisfaction AS saus
            WHERE coulist_id = :id";
        $count = DB::selectOne($countSql, [':id' => $id]);
        $paginator = new LengthAwarePaginator($data, $count->ct, $pageSize, $page, ['path' => '/home/feedbackDetail?id='.$id]);
        return view('home.coursePage.feedbackDetail',compact('dataA', 'data', 'paginator'));
    }
}
