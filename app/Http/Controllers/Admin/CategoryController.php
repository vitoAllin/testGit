<?php

namespace App\Http\Controllers\Admin;

use App\Http\Model\Category;
use App\Http\Model\Course;
use App\Http\Model\CourseList;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use DB;
use Storage;
use Redis;

class CategoryController extends CommonController
{
    //redis 键名
    public $redisKey = 'categoryTree';
    public $redisKeyToCourse = 'courseTree';
    public $redisKeyToPublish = 'publishTree';
    //get.admin/category 显示目录树
    public function index()
    {
        //在课件管理页 点击添加目录时 需要带值跳转
        $target = isset($_GET['target']) ? $_GET['target'] : '';
        //显示最全的层级结构
        //$redisKey = 'categoryTree';
        $categorys = Redis::get( $this->redisKey);
        if(!$categorys){
            $categorys = (new Category)->tree('4');
            Redis::set($this->redisKey,$categorys);
        }
//        dd($categorys);
        return view('admin.category.index')->with(['data' => $categorys, 'target' =>$target ]);
    }


    public function changeOrder()
    {
        $input = Input::all();
        $cate = Category::find($input['cate_id']);
        $cate->cate_order = $input['cate_order'];
        $re = $cate->update();
        if($re){
            $data = [
                'status' => 0,
                'msg' => '分类排序更新成功！',
            ];
        }else{
            $data = [
                'status' => 1,
                'msg' => '分类排序更新失败，请稍后重试！',
            ];
        }
        return $data;
    }

    //get.admin/category/create   添加分类
    public function create()
    {
        $data = Category::where('cate_pid',0)->get();
        return view('admin.category.add',compact('data'));
//        return redirect('admin/category');
    }

    //post.admin/category  添加分类提交
    public function store()
    {
//        dd(Input::all());
        $input = Input::except('_token');
        if(isset($input['cate_flag']) && $input['cate_flag'] != 3){
            $rules = [
                'cate_code'=>'required',
                'cate_title' => 'required',
//                'cate_description' => 'required',
            ];

            $message = [
                'cate_code.required'=>'目录名不能为空！',
                'cate_title.required'=>'目录标题不能为空！',
//                'cate_description.required'=>'目录描述不能为空！'
            ];
        }else{
            $rules = [
                'coulist_code'=>'required',
                'coulist_title' => 'required',
//                'coulist_description' => 'required',
            ];

            $message = [
                'coulist_code.required'=>'课件名不能为空！',
                'coulist_title.required'=>'课件标题不能为空！',
//                'coulist_description.required'=>'课件描述不能为空！'
            ];
        }

        //防止填写重复的cate_code
        if(isset($input['coulist_code']) && $input['coulist_flag'] == 3){
            $codeList = CourseList::where('coulist_pid', $input['coulist_pid'])->lists('coulist_code')->toArray();
            $res = in_array($input['coulist_code'], $codeList);
            if($res){
                return json_encode(['addMsg' => '3']);
            }
        }

        $validator = Validator::make($input,$rules,$message);

        if($validator->passes()){
            if(isset($input['cate_flag']) && $input['cate_flag'] != 3){
                $re = Category::create($input);
            }else{
                //是否有icon上传
                if(isset($input['coulist_icon'])){
                    $res = $this->uploadsPicture($input['coulist_icon']);
                    if($res){
                        $input['coulist_icon'] = '/public/uploads/'.$res;
                    }else{
                        return json_encode(['addMsg'=>'4']);
                    }
                }
                //是否有原题图片上传
                if(isset($input['coulist_topicPic'])){
                    $res = $this->uploadsPicture($input['coulist_topicPic']);
                    if($res){
                        $input['coulist_topicPic'] = '/public/uploads/'.$res;
                    }else{
                        return json_encode(['addMsg'=>'4']);
                    }
                }
                $re = CourseList::create($input);
            }

            if($re){
                //添加成功
                Redis::del($this->redisKey);
                Redis::del($this->redisKeyToCourse);
                Redis::del($this->redisKeyToPublish);
                return json_encode(['addMsg' => '1']);
            }else{
                //添加失败
                return json_encode(['addMsg' => '0']);
            }
        }else{
            //验证失败
            return json_encode(['addMsg' => '2']);
        }
    }

    //post.admin/category  添加顶级目录
    public function addTopCategory()
    {
        $input = Input::except('_token');
//        dd($input);
        $rules = [
            'cate_code'=>'required',
            'cate_title' => 'required',
            'cate_description' => 'required',
        ];

        $message = [
            'cate_code.required'=>'分类名不能为空！',
            'cate_title.required'=>'分类标题不能为空！',
            'cate_description.required'=>'分类描述不能为空！'
        ];

        $validator = Validator::make($input,$rules,$message);

        if($validator->passes()){
            $re = Category::create($input);
            if($re){
                //添加成功
                Redis::del($this->redisKey);
                return redirect('admin/category ')->with('errors','添加成功');
            }else{
                //添加失败
                return back()->with('errors','数据填充失败，请稍后重试！');
            }
        }else{
            //验证失败
           return back()->with('errors','验证失败，请输入正确信息！');
        }
    }

    //put.admin/category/{category}    更新分类
    public function update($cate_id)
    {
//        dd(Input::all());
        $input = Input::except('_token','_method');
        //判断是目录修改还是课件修改
        if(!isset($input['coulist_code'])){
            $re = Category::where('cate_id', $cate_id)->update($input);
        }else{
            //如果是课件通过courseList更新
            //判断是否有图片更新
            if(isset($input['coulist_icon'])){
                $res = $this->uploadsPicture($input['coulist_icon']);
                if($res){
                    $input['coulist_icon'] = '/public/uploads/'.$res;
                }else{
                    return json_encode(['addMsg'=>'4']);
                }
            }
            //判断是否有原题图片更新
            if(isset($input['coulist_topicPic'])){
                $res = $this->uploadsPicture($input['coulist_topicPic']);
                if($res){
                    $input['coulist_topicPic'] = '/public/uploads/'.$res;
                }else{
                    return json_encode(['addMsg'=>'4']);
                }
            }
            $re = CourseList::where('coulist_id', $cate_id)->update($input);
        }
        if($re){
            if(!isset($input['coulist_code'])){
                Redis::del($this->redisKey);
                Redis::del($this->redisKeyToCourse);
                Redis::del($this->redisKeyToPublish);
                return redirect('admin/category/')->with(['upRes' => 1, 'upArg' => $cate_id, 'upMsg' => '目录信息更新成功' ]);
            }else{
                Redis::del($this->redisKey);
                Redis::del($this->redisKeyToCourse);
                Redis::del($this->redisKeyToPublish);
                return redirect('admin/category/')->with(['upRes' => 1, 'upArg' => $cate_id + 10000, 'upMsg' => '目录信息更新成功' ]);
            }
        }else{
            if(!isset($input['coulist_code'])){
                return redirect('admin/category/')->with(['upRes' => 2, 'upArg' => $cate_id, 'upMsg' => '目录信息更新失败，请稍后重试！']);
            }else{
                return redirect('admin/category/')->with(['upRes' => 2, 'upArg' => $cate_id + 10000, 'upMsg' => '目录信息更新失败，请稍后重试！']);
            }
        }
    }

    //delete.admin/category/{category}   删除单个目录
    public function destroy($cate_id, Request $request)
    {
        //删除函数
        function delCate($cate_id, $cateFlag){
            if($cateFlag == 3){
                $re = CourseList::where('coulist_id', $cate_id)->delete();
            }else{
                $re = Category::where('cate_id',$cate_id)->delete();
            }
            if($re){
                return with(['delMsg' => '1']);
            }else{
                return with(['delMsg' => '2']);
            }
        }

        //查看当前分类下面是否有子分类
        $cate = Category::where('cate_id',$cate_id)->select('cate_id', 'cate_flag')->first();
        $courList = CourseList::where('coulist_id', $cate_id)->select('coulist_id')->first();
        if($request->cateFlag == 3){
            $courseIdArr = DB::table('course')->pluck('cou_pid');
            if(in_array($courList->coulist_id, $courseIdArr)){
                return with(['errors'=>'当前课件下面有课件包，不能删除']);
            }else{
                Redis::del($this->redisKey);
                return delCate($cate_id, $request->cateFlag);
            }
        }elseif($request->cateFlag == 2){
            $courseListIdArr = DB::table('courselist')->pluck('coulist_pid');
            if(in_array($cate->cate_id, $courseListIdArr)){
                return with(['errors'=>'当前单元下面有课件，不能删除']);
            }else{
                Redis::del($this->redisKey);
                return delCate($cate_id, $request->cateFlag);
            }
        }else{
            $cateIdArr = DB::table('category')->pluck('cate_pid');
            if(in_array($cate->cate_id, $cateIdArr)){
                return with(['errors'=>'当前目录下面有子目录，不能删除']);
            }else{
                Redis::del($this->redisKey);
                return delCate($cate_id, $request->cateFlag);
            }
        }
    }

//    点击标签显示分类
    public function info(Request $request)
    {
        $cateFlag = $request -> flag;
        //判断是目录还是课件
        if($request -> flag != 3){
            $cateInstance = new Category;
            $cateData = $cateInstance -> cateData( $request -> cateid);
            if($cateFlag == 2){
                $modelRes = $cateInstance->cateModel($request -> cateid);
            }
        }else{
            //如果是课件去 courseList查询
            $cateInstance = new CourseList();
            $cateData = $cateInstance -> courseData($request -> cateid);
            $cateEle = (new Category) ->cateModel($cateData -> pid);
        }
//        dd($cateData,$cateData->pid, $cateEle);
        return view('admin.category.catepage')->with([ 'catedata' => $cateData, 'cateEle' => (isset($cateEle) ? $cateEle : []), 'catemodel' => (isset($modelRes) ? $modelRes : [])]);
    }

    //设置发布课件 coulist_publish字段
    public function setCoursePublish(Request $request){
        //修改coulist_publish 字段值
        $couPublish = CourseList::where('coulist_id', $request->couListId)->value('coulist_publish');
        if($couPublish){
            CourseList::where('coulist_id', $request->couListId)->update(['coulist_publish' => 0]);
        }else{
            CourseList::where('coulist_id', $request->couListId)->update(['coulist_publish' => 1]);
        }
    }

    //设置发布课件 coulist_publish字段
    public function setCoursePublishOpen(Request $request){
        //修改coulist_publish_open 字段值
        $couPublish = CourseList::where('coulist_id', $request->couListId)->value('coulist_publish_open');
        if($couPublish){
            CourseList::where('coulist_id', $request->couListId)->update(['coulist_publish_open' => 0]);
        }else{
            CourseList::where('coulist_id', $request->couListId)->update(['coulist_publish_open' => 1]);
        }
    }
    
    //上传文件
    public function uploadsPicture($pic){
        $file = $pic;
        //判断图片大小
        $size =ceil( $file->getSize()/1024);
        if($size <= 300){
            $ext = $file->getClientOriginalExtension();     // 扩展名
            $realPath = $file->getRealPath();   //临时文件的绝对路径
            // 上传文件
            $filename = date('Y-m-d-H-i-s') . '-' . uniqid() . '.' . $ext;
            // 使用我们新建的uploads本地存储空间（目录）
            $bool = Storage::disk('uploads')->put($filename, file_get_contents($realPath));
            if($bool){
                return $filename;
            }
        }else{
            return false;
        }
    }
}
