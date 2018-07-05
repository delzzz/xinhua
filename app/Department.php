<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;
    protected $table = 'department';
    protected $primaryKey='department_id';
    protected $fillable = ['dname','description'];
    protected $dates = ['deleted_at'];


    //部门列表
    function lists(){
        return Department::all();
    }

    //检查部门名
    function checkName($dName,$did = null){
        if(!empty($did)){
            $count = Department::where('dname',$dName)->where('department_id','<>',$did)->count();
        }
        else{
            $count = Department::where('dname',$dName)->count();
        }
        if($count>0){
            return true;
        }
        else{
            return false;
        }
    }

    //添加修改部门
    function add($did,$fields){
        return $flag = Department::updateOrCreate(
            ['department_id' => $did],
            $fields
        );
    }

    //删除部门
    function del($did)
    {
        $idArr = explode(',',$did);
        foreach ($idArr as $key => $value){
            $flag = Department::find($value)->delete();
        }
        return $flag;
    }



    //根据did查出部门名
    function getName($did){
        $department = Department::find($did);
        return $department['dname'];
    }

    //部门信息
    function info($did){
        return Department::find($did);
    }
}
