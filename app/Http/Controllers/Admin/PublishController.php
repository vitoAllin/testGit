<?php

namespace App\Http\Controllers\Admin;

use App\Http\Model\Category;
use App\Http\Model\Course;
use App\Http\Model\CourseList;
use App\Http\Model\PubCate;
use App\Http\Model\publish;
use App\Http\Model\Publishtest;
use App\Http\Model\Version;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use DB;
use Redis;

class PublishController extends CommonController
{
    const DOWNLOAD_SERVER_URL = 'http://develop.x-real.cn:89/';
    public $redisKey = 'publishTree';
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * 发布课件
     */
    public function index()
    {
        $cateInstance = new Category;
        //只显示到课本层级
        $categorys = Redis::get( $this->redisKey);
        if(!$categorys){
            $categorys = $cateInstance->tree('2');
            Redis::set($this->redisKey,$categorys);
        }
        //$categorys = $cateInstance->tree('2');
        return view('admin.publish.publish')->with(['data' => $categorys]);
    }

    /**
     * @param Request $request
     * @return $this
     * 发布页面
     */
    public function publish_info(Request $request)
    {
        $cateInstance = new Category;
        $categorys = Redis::get( $this->redisKey);
        if(!$categorys){
            $categorys = $cateInstance->tree();;
            Redis::set($this->redisKey,$categorys);
        }
        //$categorys = $cateInstance->tree();
        $cateData = $cateInstance->cateData($request->cateid);
        $cateFlag = $cateInstance->where('cate_id', $request->cateid)->select('cate_flag')->first();
        if ($cateFlag->cate_flag == 1) {
            $modelRes = $cateInstance->cateModel($request->cateid);
        }
        //查看当前课件发布状态 正式版本
        $pub = new Publish();
        $pubOfficial = $pub->where('book_id', $request->cateid)->where('pub_istest', 0)->count();

        //查看当前课件发布状态 测试版本
        $pubTests = new Publishtest();
        $pubTest= $pubTests->where('book_id', $request->cateid)->where('pub_istest', 1)->count();

        //查找该课本的所有课件
        $elementList = Category::where('cate_pid',$request->cateid)->lists('cate_id');
        $courseList = [];
        $num = 0;
        foreach($elementList as $k => $v){
            $cou = CourseList::where('coulist_pid', $v)
                ->select('coulist_id', 'coulist_title', 'coulist_pid', 'coulist_code', 'coulist_publish', 'coulist_publish_open', 'coulist_developer', 'coulist_page', 'coulist_designer')->orderBy('coulist_code')->get()->toArray();
           foreach($cou as $key => $value){
               $courseList[$num] = $value;
               $num += 1;
           }
        }

        //获取所有的版本号
        $allVersion = Version::getAllVersion();

        //获取当前版本号已经发布的课件
        //获取最大的版本号
        $versionMaxNum = Version::where('version_code', Version::max('version_code'))->value('version_id');
        $publishCateId = PubCate::where('pub_version_id', $versionMaxNum)->where('pub_book_id', $request->cateid)->lists('pub_coulist_id')->toArray();

//        dd($courseList);
        return view('admin.publish.publishpage')->with(['data' => $categorys, 'catedata' => $cateData, 'catemodel' => (isset($modelRes) ? $modelRes : []), 'pubtest' => $pubTest, 'pubofficial' => $pubOfficial, 'courselist'=>$courseList, 'allVersion'=>$allVersion , 'publishCate'=>$publishCateId]);
    }

    //发来请求，返回课本的正式版配置文键信息
    public function bookVersion(Request $request)
    {
        $maxId = $request->maxid;
        $versionId = Version::where('version_code', $request->versionCode)->value('version_id');
        if ($maxId && $versionId) {
            $pub = new Publish();
            $pubIdArr = $pub->checkIdArr($maxId, $versionId);
//            dd($pubIdArr);
            return $pubIdArr;
        }
    }

    //发来请求，返回课本的测试版配置文键信息
    public function bookVersionTest(Request $request)
    {
        $maxId = $request->maxid;
        if ($maxId) {
            $pub = new Publishtest();
            $pubIdArr = $pub->checkIdArr($maxId);
            //dd($pubIdArr);
            return $pubIdArr;
        }
    }

    /**
     * @param Request $request
     * @return array
     * 发布课件 生成配置文件
     */
    public function publishCourse(Request $request)
    {
//        dd($request->all());
        //开始请求的时间
        list($t1, $t2) = explode(' ', microtime());
        $st = (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
        $bookId = $request->bookid;
        //获取发布的版本号
        $version = Version::where('version_id', $request->version)->first();
        //发布课件生成配置信息
        $pubArr = [];
        $book = new Category();
        $bookInfo = $book->where('cate_id', $bookId)->first();
        $bookPid = $bookInfo->cate_pid;
        $pInfo = $book->where('cate_id', $bookPid)->first();
        //向下查找课件数据
        $pubArr['chapters'] = $this->elementCate($bookId, $request->istest, $request->version , $request->courseIdArr);
        //判断是否有课件
        if( count($pubArr['chapters']) ){
            //如果有课件 添加其他字段
            $pubArr['bookName'] = $bookInfo->cate_title;
//            $pubArr['bookId'] = $pInfo->cate_name . '-' . $bookInfo->cate_name;
            $pubArr['bookId'] = $bookInfo->cate_code;
            $pubArr['timestamp'] = date('Y-m-d H:i:s');
        }else{
            return json_encode(['msg'=>'当前课本没有上传课件，不能发布']);
        }
//        dd($pubArr);
        //测试版本需要写入不同的文件夹下面
        if($request->istest){
            $res = file_put_contents(base_path().'/bookconfigclose/'.$pubArr['bookId'].'.json', json_encode($pubArr));
        }else{
            //判断文件夹是否存在
            $hasFile = file_exists(base_path().'/bookconfig/'.$version->version_code);
            if($hasFile){
                $res = file_put_contents(base_path().'/bookconfig/'.$version->version_code.'/'.$pubArr['bookId'].'.json', json_encode($pubArr));
            }else{
                return '版本信息错误，没有此版本';
            }

        }
        if($res){
            if($request->istest){
                $publish = new Publishtest();
                $publish->pub_name = $pubArr['bookId'];
                $publish->pub_title = $pubArr['bookName'];
//                $publish->pub_url = 'http://develop.x-real.cn:89/bookconfigclose/'.$pubArr['bookId'].'.json';
                $publish->pub_url = 'bookconfigclose/'.$pubArr['bookId'].'.json';
                $publish->pub_istest = $request->istest;
                $publish->pub_time = date('Y-m-d H:i:s');
                $publish->book_id = $bookId;
                $publish->save();
                //结束请求的时间
//                list($t1, $t2) = explode(' ', microtime());
//                $et = (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
                return json_encode(['status'=>'1' ,'msg'=>'发布成功']);
            }else{
                $publish = new Publish();
                $publish->pub_name = $pubArr['bookId'];
                $publish->pub_title = $pubArr['bookName'];
                $publish->pub_url = 'bookconfig/'.$version->version_code.'/'.$pubArr['bookId'].'.json';
                $publish->pub_istest = 0;
                $publish->pub_time = date('Y-m-d H:i:s');
                $publish->book_id = $bookId;
                $publish->pub_version = $request->version;
                $publish->save();
                //结束请求的时间
                list($t1, $t2) = explode(' ', microtime());
                $et = (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
//                var_dump($et - $st);
                return json_encode(['status'=>'1' ,'msg'=>'发布成功', 'time'=>$et - $st]);
            }
        }else{
            return json_encode(['status'=>'0' ,'msg'=>'发布失败，请稍后再试']);
        }
    }

    //获取单元配置信息
    public function elementCate($bookId, $isTest, $versionId, $courseIdArr)
    {
        if(!$isTest){
            PubCate::where('pub_version_id', $versionId)->where('pub_book_id', $bookId)->delete();
        }
        //获取course表的cou_pid 判断是否存在课件
        $coursePidList = Course::lists('cou_pid')->toArray();
        $element = new Category();
        $elementInfo = $element->where('cate_pid', $bookId)->orderBy('cate_order', 'asc')->get();
        //生成单元信息
        $elementArr = [];
        foreach ($elementInfo as $key => $value) {
            //每次循环单元时，先将课件数组清空
            $courseArr = [];
            //获取课件配置信息
            //添加 是否发布（coulist_publish）判断
            if($isTest){
                //测试版的发布
                $courseInfo = (new CourseList()) ->where('coulist_pid', $value->cate_id)->where('coulist_publish', '1')->orderBy('coulist_order', 'asc')->get();
            }elseif(!$isTest){
                //正式版的发布
//                $courseInfo = (new CourseList()) ->where('coulist_pid', $value->cate_id)->where('coulist_publish_open', '1')->orderBy('coulist_order', 'asc')->get();
                $courseInfo = (new CourseList()) ->where('coulist_pid', $value->cate_id)->whereIn('coulist_id', $courseIdArr)->orderBy('coulist_order', 'asc')->get();
            }

            //判断当前单元下面是否有课件存在，没有就跳出循环
            if(!count($courseInfo)){
                continue;
            }

            //查找课件信息返回到这里
            foreach ($courseInfo as $k => $v) {
                //检查课件下面是否存在课件包
                if(in_array($v->coulist_id, $coursePidList)){
                    $courseArr  = [];
                    $courseWare = $this->courseCate($v , $isTest);
                    $courseArr[$v->coulist_id] = $courseWare;
                    $elementArr[$value->cate_id][$v->coulist_id] = $courseArr[$v->coulist_id];
                    //将正式版发布的课件 存入到pub_cate表里面，做版本管理
                    if(!$isTest){
                        //先把原来记录好的数据删除掉
//                      if(!in_array($v->coulist_id, PubCate::getPubCouListId()) || !in_array($versionId, PubCate::getPubVersionNum())){
                        $pubCate = new PubCate();
                        $pubCate->pub_coulist_id = $v->coulist_id;
                        $pubCate->pub_version_id = $versionId;
                        $pubCate->pub_book_id = $bookId;
                        $pubCate->pub_create_time = date('Y-m-d H:i:s');
                        $pubCate->save();
//                     }
                    }
                }else{
                    continue;
                }
            }
            //验证课件目录下是否有课件包
            if(isset($courseArr) && count($courseArr)){
                $elementArr[$value->cate_id]['capterName'] = $value->cate_title;
            }else{
                continue;
            }
        }
        return $elementArr;
    }

    //获取具体课件配置信息
    public function courseCate($param, $isTest)
    {
        //修改逻辑 发布时通过选择允许发布的课件包来选择
        //判断是正式版还是测试版
        if($isTest){
            $courseInfo = Course::where('cou_pid',$param->coulist_id)->where('cou_publish', '1')->get()->toArray();
        }else{
            $courseInfo = Course::where('cou_pid',$param->coulist_id)->where('cou_publish_open', '1')->get()->toArray();
        }
        //修改逻辑之后
        if($courseInfo){
            $courseArr = [];
            $courseArr['nameMsg'] = $courseInfo['0']['cou_name'];
            $courseArr['sceneID'] = $param->coulist_code;
            $courseArr['versionNum'] = $courseInfo['0']['cou_version'];
            $courseArr['AR'] = $courseInfo['0']['cou_isAr'] ? "true" : "false";
            $courseArr['page'] = $param ->coulist_page;
            $courseArr['page1'] = $param ->coulist_page1;
            $courseArr['url'] = $courseInfo['0']['cou_downurl'];
            $courseArr['order'] = $param ->coulist_order;
            $courseArr['qnumber'] = $param ->coulist_qnumber;
            $courseArr['keyword'] = $param ->coulist_keyword;
            $courseArr['isPerfect'] = $param->coulist_isPerfect ? "true" : "false" ;
            $courseArr['isNew'] = $param->coulist_isNew ? "true" : "false" ;

            if(!empty($param ->coulist_icon)){
                $courseArr['icon'] = url(asset($param ->coulist_icon));
            }else{
                $courseArr['icon'] = $param ->coulist_icon;
            }
            $courseArr['isVideo'] = $courseInfo['0']['cou_isVideo'] ? "true" : "false";
            $courseArr['isFree'] = $courseInfo['0']['cou_isFree'] ? "false" : "true";
            $courseArr['resId'] = $courseInfo['0']['cou_res_id'];
            $courseArr['resUrl'] =  $courseInfo['0']['cou_res_url'] ? url(asset($courseInfo['0']['cou_res_url'])) : '';
            return $courseArr;
        }
    }

    //删除配制文件
    public function delConfigFile(Request $request)
    {
        $configName = Publish::where('book_id', $request->bookid)->value('pub_name');
        if($request->istest){
            $hasFile = file_exists(base_path().'/bookconfigclose/'.$configName.'.json');
            if($hasFile){
                $res = unlink(base_path().'/bookconfigclose/'.$configName.'.json');
                if($res)
                    return json_encode(['status'=>'1' ,'msg'=>'内测版配置文件删除成功']);
                else
                    return json_encode(['status'=>'0' ,'msg'=>'内测版配置文件删除失败，请稍后再试']);
            }else{
                return json_encode(['status'=>'3' ,'msg'=>'没有此文件，请先发布']);
            }
        }else{
            $hasFile = file_exists(base_path().'/bookconfig/'.$configName.'.json');
            if($hasFile){
                $res = unlink(base_path().'/bookconfig/'.$configName.'.json');
                if($res)
                    return json_encode(['status'=>'1' ,'msg'=>'配置文件删除成功']);
                else
                    return json_encode(['status'=>'0' ,'msg'=>'配置文件删除失败，请稍后再试']);
            }else{
                return json_encode(['status'=>'3' ,'msg'=>'没有此文件，请先发布']);
            }
        }

    }

    //切换版本号 返回那些课件处于发布中
    public function changeVersion(Request $request)
    {
        $publishCate = PubCate::where('pub_book_id', $request->bookid)
            ->where('pub_version_id', $request->version)
            ->lists('pub_coulist_id')->toArray();
        return json_encode(['status'=>'1' ,'msg'=>$publishCate]);
    }
}
