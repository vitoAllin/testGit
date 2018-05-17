<?php

namespace App\Http\Controllers\Admin;

use App\Http\Model\Course;
use App\Http\Model\Category;
use App\Http\Model\CourseList;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Storage;
use Redis;
use Qiniu\Auth;
use Qiniu\Storage\ResumeUploader;
use Qiniu\Config;

class CourseController extends CommonController
{
    public $redisKey = 'courseTree';
    //get.admin/course
    public function index(Request $request)
    {
//        var_dump($request->cid);
        $courseData = (new Course)->where('cou_pid', $request->cid)->leftjoin('category', 'category.cate_id', '=', 'course.cou_pid')->orderBy('course.cou_time', 'desc')->get();
        return json_encode($courseData);
    }

    //get.admin/course/create 添加课件
    public function create(Request $request)
    {
        $cateId = $request->cateid;
        $data = (new Category())->where('cate_id', $cateId)->first();
        return view('admin.course.index', compact('data'));
    }

    //post.admin/course  添加课件提交写入数据库
    public function store()
    {
        $input = Input::except('_token');
        $input['cou_time'] = date('Y-m-d H:i:s');
        $rules = [
            'cou_name' => 'required',
            'cou_isAr' => 'required',
        ];

        $message = [
            'cou_name.required' => '课件名称不能为空！',
            'cou_isAr.required' => '课件是否为Ar不能为空',
        ];
        //uploadify上传方式
        $validator = Validator::make($input, $rules, $message);
        if ($validator->passes()) {
            //把新上传的课件设置为置顶发布，把其他的课件包设置为不发布
            Course::where('cou_pid', Input::get('cou_pid'))->update(['cou_publish' => 0]);
            //判断是否有课件资源上传
            if(isset($input['uploadRes']) && $input['cou_isVideo'] == 1){
                $res = $this->uploadsPicture($input['uploadRes']);
                if($res){
                    $input['cou_res_url'] = '/public/courseResource/'.$res;
                    //获取当前课件课件资源最大的id
                    $cou_res_id = Course::where('cou_pid', $input['cou_pid'])->max('cou_res_id');
                    if(!$cou_res_id){
                        $input['cou_res_id'] = 1;
                    }
                    $input['cou_res_id'] = $cou_res_id + 1;
                }
                //删除上传课件资源
                unset($input['uploadRes']);
            }
            //判断如果只需要课件资源但不上传新的课件资源，则取出原来的课件资源id和url填写进来
            if(!isset($input['uploadRes']) && $input['cou_isVideo'] == 1){
                //获取当前课件课件资源最大的id
                $cou_res_id = Course::where('cou_pid', $input['cou_pid'])->max('cou_res_id');
                $cou_res_url = Course::where('cou_pid', $input['cou_pid'])
                                ->where('cou_res_id', $cou_res_id)
                                ->value('cou_res_url');
                if(!$cou_res_id){
                    $input['cou_res_id'] = 1;
                }
                $input['cou_res_id'] = $cou_res_id ;
                $input['cou_res_url'] = $cou_res_url;
            }
            $re = Course::create($input);
            Course::where('cou_id', $re->cou_id)->update(['cou_version' => $re->cou_id]);
            if ($re) {
                //查找课件所在的单元
                $cateId = CourseList::where('coulist_id', Input::get('cou_pid'))->value('coulist_pid');
                $element = Category::where('cate_id', $cateId)->value('cate_id');
                return redirect('admin/course/show')->with(['couAddStatus'=> 1, 'addArg'=> $element, 'addMsg'=>'上传课件成功']);
            } else {
                return back()->with('errors', '上传课件失败，请稍后重试！');
            }
        } else {
            return back()->with(['couAddStatus'=> 2, 'addMsg'=>'上传课件失败，请填写完全在上传']);
        }
    }

    //使用uploadify上传文件到七牛云
    public function qnUpload(Request $request)
    {
        //文件上传部分
        $file = Input::file('Filedata');
        $size = $_FILES['Filedata']['size'];
        if ($file->isValid()) {
//            $disk = storage::disk('qiniu');
//            $extension = $file->getClientOriginalExtension(); //上传文件的后缀.
//            $newName = date('YmdHis') . mt_rand(100, 999) . '.' . $extension;
////            $filInfo = $disk->put($newName, file_get_contents($file->getRealPath()));
//            $filInfo = $disk->put($newName, file_get_contents($file->getRealPath()));
//            $exists = Storage::disk('qiniu')->exists($newName);
//            $token = $disk->getDriver()->uploadToken($newName);
//            $url = $disk->downloadUrl($newName);
//            //dd($exists, $token, $url);
//            $urlSave = 'http://p2fr1surw.bkt.clouddn.com/' . $newName;
//            //私有空间设置
//            $accessKey ="mOpyI5u03qdWgU7jSFv_iMfxcMzn8UbvQ2ee5wlc";
//            $secretKey = "kWo1lErQqWshNEqtL9wvC4Kb0e7EyMMH7VaMHuCu";
//            // 构建Auth对象
//            $auth = new Auth($accessKey, $secretKey);
//            // 私有空间中的外链 http://<domain>/<file_key>
//            $baseUrl = $urlSave;
//            // 对链接进行签名
//            $signedUrl = $auth->privateDownloadUrl($baseUrl);

            $accessKey = config('filesystems.disks.qiniu.access_key');
            $secretKey = config('filesystems.disks.qiniu.secret_key');
            $bucketName = config('filesystems.disks.qiniu.bucket');
            $auth = new Auth($accessKey, $secretKey);
            $token = $auth->uploadToken($bucketName);

            $extension = $file->getClientOriginalExtension(); //上传文件的后缀.
            $mime = $file->getClientMimeType(); //上传文件的后缀.
            $newName = date('YmdHis') . mt_rand(100, 999) . '.' . $extension;
            $config = new Config();

            $upManager = new ResumeUploader($token, $newName, fopen($file->getRealPath(), 'r'), $size, array(), $mime, $config);

            // list($ret, $error) = $upManager->putFile($token, $newName, $file->getRealPath());
            list($ret, $error) = $upManager->upload($newName);
            $urlSave = 'http://p2fr1surw.bkt.clouddn.com/' . $newName;

            return json_encode(['cname' => $newName, 'csize' => $size, 'curl' => $urlSave]);
        }

    }

    public function qndel($courseName)
    {
        $disk = storage::disk('qiniu');
        $res = $disk->delete($courseName);
        if ($res) {
            return true;
        }
    }


    //delete.admin/course/{course}   删除单个课件
    public function destroy($cou_id)
    {
        //删除课件存储：
        // TODO 确定文件是否要被真的删除;
//        $courseName = Course::where('cou_id',$cou_id)->value('cou_savename');
//        if($courseName){
//           $res =  qndel($courseName);
//        }

        //数据库删除课件信息
        $re = Course::where('cou_id', $cou_id)->delete();
        if ($re) {
            $data = [
                'status' => 1,
                'msg' => '课件删除成功！',
            ];
        } else {
            $data = [
                'status' => 0,
                'msg' => '课件删除失败，请稍候再试！',
            ];
        }
        return $data;
    }


    //显示文件树 和 课件列表
    public function show()
    {
        $cateInstance = new Category;
        $categorys = Redis::get( $this->redisKey);
//        dd($categorys);
        if(!$categorys){
            $categorys = $cateInstance->courseTree();
            Redis::set($this->redisKey,$categorys);
        }
//        $categorys = $cateInstance->courseTree();
        return view('admin.course.index')->with('data', $categorys);
    }

    //课件列表 课件添加页
    public function courseList(Request $request)
    {
        $cateInstance = new Category();
        $categorys = Redis::get( $this->redisKey);
        if(!$categorys){
            $categorys = $cateInstance->courseTree();
            Redis::set($this->redisKey,$categorys);
        }
//        $categorys = $cateInstance->courseTree();
        $cateData = $cateInstance->cateData($request->cateid);
        $cateFlag = $cateInstance->where('cate_id', $request->cateid)->select('cate_flag')->first();
        if ($cateFlag->cate_flag == 2) {
            $courseInstance = new CourseList();
            $modelRes = $courseInstance->courseModel($request->cateid);
        }
//        dd($modelRes);
        return view('admin.course.coursepage')->with(['data' => $categorys, 'catedata' => $cateData, 'catemodel' => (isset($modelRes) ? $modelRes : [])]);
    }

    //设置发布置顶课件包
    public function topCourse(Request $request){
        //修改数据库 cou_publish字段
        $couPid  = $request -> courseBagPid;
        $step1 = Course::where('cou_pid', $couPid)->update(['cou_publish' => 0]);
        $step2 = Course::where('cou_id', $request->courseBagId)->update(['cou_publish' => 1]);
        if($step1 && $step2){
            $data = [
                'status' => 1,
                'msg' => '操作成功！',
            ];
        }else{
            $data = [
                'status' => 0,
                'msg' => '操作失败，请稍候再试！',
            ];
        }
        return $data;
    }

    //设置发布置顶课件包 正式版
    public function topCourseOpen(Request $request){
        //修改数据库 cou_publish字段
        $couPid  = $request -> courseBagPid;
        $step1 = Course::where('cou_pid', $couPid)->update(['cou_publish_open' => 0]);
        $step2 = Course::where('cou_id', $request->courseBagId)->update(['cou_publish_open' => 1]);
        if($step1 && $step2){
            $data = [
                'status' => 1,
                'msg' => '操作成功！',
            ];
        }else{
            $data = [
                'status' => 0,
                'msg' => '操作失败，请稍候再试！',
            ];
        }
        return $data;
    }

    //修改课件资源的课件资源名
    public function updateCourseName(Request $request){
//        var_dump($request->all());
        Course::where('cou_id', $request->courseId)->update(['cou_name'=>$request->courseName]);
        $data = [
            'status' => 1,
            'msg' => '修改成功！生效需要重新发布',
            'result'=>$request->courseName
        ];
        return $data;
    }


    //上传文件
    public function uploadsPicture($pic){
        $file = $pic;
        //判断图片大小
        $ext = $file->getClientOriginalExtension();     // 扩展名
        $realPath = $file->getRealPath();   //临时文件的绝对路径
        // 上传文件
        $filename = date('YmdHis').uniqid() . '.' . $ext;
        // 使用我们新建的uploads本地存储空间（目录）
        $bool = Storage::disk('courseUploads')->put($filename, file_get_contents($realPath));
        if($bool){
            return $filename;
        }else{
            return false;
        }
    }
}
