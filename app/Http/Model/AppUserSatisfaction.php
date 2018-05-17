<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;

class AppUserSatisfaction extends Model
{
    protected $table = 'app_user_satisfaction';
    protected $primaryKey='saus_id';
    public $timestamps=false;
    protected $guarded=[];

    /*
     * 易用性
     */
    const OPERATE_EASY = 1;
    /*
     * 课件设计
     */
    const DESIGN_STYLE  = 2;
    /*
     * 教学中是否有用
     */
    const USE_IN_CLASS = 3;
    /*
     * 改进意见
     */
    const IMPROVEMENTS = 4;

    //设置表结构
    protected static $type = array(
        self::OPERATE_EASY=>'操作便捷性',
        self::DESIGN_STYLE=>'课件设计风格',
        self::USE_IN_CLASS=>'课上使用意愿',
    );

    //返回表的结构
    public static function  getType()
    {
        return self::$type;
    }

    /**
     * [addNew 新增评价]
     * @Author   Vito
     * @DateTime 2018-05-09T17:57:19+0800
     * @param    [type]                   $userId      [description]
     * @param    [type]                   $coulistId   [description]
     * @param    [type]                   $operateEasy [description]
     * @param    [type]                   $designStyle [description]
     * @param    [type]                   $useInClass  [description]
     * @param    [type]                   $content     [description]
     */
    public function addNew($userId, $coulistId, $operateEasy, $designStyle, $useInClass, $content)
    {
        $data = array(
            'app_user_id' => $userId,
            'coulist_id' => $coulistId,
            'saus_operate_easy' => $operateEasy,
            'saus_design_style' => $designStyle,
            'saus_use_in_class' => $useInClass,
            'saus_content' => $content,
            'saus_createTime' => date('Y-m-d H:i:s'),
        );
        return self::create($data);
    }



}