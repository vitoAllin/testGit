<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Request;
use DB;

/**
 * Class Category
 * @package App\Http\Model
 */
class Category extends Model
{
    protected $table='category';
    protected $primaryKey='cate_id';
    public $timestamps=false;
    protected $guarded=[];

    /**
     * @return mixed $category
     *
     */
    //获取树目录
    public function tree($cate_except = '3')
    {
        $courseList = CourseList::select('coulist_id as id' ,  'coulist_title as title', 'coulist_pid as pid',
            'coulist_description as description', 'coulist_order as order', 'coulist_flag as flag');
        //获取树导航数据菜单
        $category = $this->where('cate_flag','!=',$cate_except)
                        ->select('cate_id as id','cate_title as name','cate_pid as pid',
                            'cate_description as description', 'cate_order as order', 'cate_flag as flag')
                        ->unionAll($courseList)
                        ->orderBy('order', 'asc')
                        ->get();
        foreach($category as $k => $v){
            $v['mid']= $v -> id;
            if($v['flag'] == 3){
                //构造id 防止目录树id冲突
                $v->id = $v -> id + 10000;
            }
        }
        return $category;
    }

    //获取课件资源管理树
    public function courseTree($cate_except = '3'){
        $category = $this->where('cate_flag','!=',$cate_except)
                        ->select('cate_id as id','cate_title as name','cate_pid as pid',
                            'cate_description as description', 'cate_order as order', 'cate_flag as flag')
                        ->orderBy('order', 'asc')
                        ->get();
//        dd($category);
        return $category;
    }

    //点击获取分类数据
    public function cateData($cateId)
    {
        $cateData = $this->where('cate_id',$cateId )->orderBy('cate_order','asc')->select('cate_id as id','cate_code as code', 'cate_title as title','cate_pid as pid', 'cate_description as description', 'cate_flag as flag', 'cate_order as order')->first();
        return $cateData;
    }


    //当flag == 3时 需要获取课件的上一级目录信息（单元信息） 在添加课件，并且选择了flag = 3 的时候
    public function cateModel($cateId){
        $cateModels = $this->where('cate_id',$cateId)->select('cate_id as id', 'cate_description as description','cate_pid as pid')->first();
//        $cateElement = $this->where('cate_id', $cateModels->pid)->select('cate_id as id', 'cate_description as description')->first();
//        return $cateElement;
        return $cateModels;
    }




}


