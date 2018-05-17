<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;

class Managers extends Model
{
    protected $table='manager';
    protected $primaryKey='mgr_id';
    public $timestamps=false;
    protected $guarded=[];

    //新增一条管理员记录
    public function addManager($request){
        $this->mgr_name = $request->orgMgrName;
        $this->mgr_phone = $request->orgMgrPhone;
        $this->mgr_create_time = date('Y-m-d H:i:s');
        $this->save();
        return $this->mgr_id;
    }
}
