<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;

class SchoolManagerRelation extends Model
{
    protected $table='schandmgr';
    protected $primaryKey='rel_id';
    public $timestamps=false;
    protected $guarded=[];

    public function addRelation($schoolId, $managerId){
        $this->school_id = $schoolId;
        $this->manager_id = $managerId;
        $this->save();
    }
}
