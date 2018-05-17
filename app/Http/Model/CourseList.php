<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;

class CourseList extends Model
{
    protected $table='courselist';
    protected $primaryKey='coulist_id';
    public $timestamps=false;
    protected $guarded=[];

    public function courseData($cateId){
        $cateData = $this->where('coulist_id',$cateId )->orderBy('coulist_order','asc')->select('coulist_id as id','coulist_name as name', 'coulist_title as title','coulist_pid as pid', 'coulist_description as description', 'coulist_flag as flag', 'coulist_order as order', 'coulist_code as code', 'coulist_page as page', 'coulist_page1 as page1', 'coulist_qnumber as qnumber', 'coulist_keyword as keyword', 'coulist_designExplain as designExplain', 'coulist_schemer as schemer', 'coulist_instance as instance', 'coulist_2instance as instance2', 'coulist_proofread as proofread', 'coulist_icon as icon', 'coulist_topicPic as topicPic', 'coulist_isPerfect as isPerfect', 'coulist_isNew as isNew', 'coulist_developer as developer', 'coulist_designer as designer')->first();
        return $cateData;
    }

    //获取当前单元下面的课件
    public function courseModel($cateId){
        $courseModel = $this->where('coulist_pid',$cateId)->orderBy('coulist_order','asc')->get();
        return $courseModel;
    }
}
