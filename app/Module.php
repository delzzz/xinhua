<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Module extends Model
{
    use SoftDeletes;
    protected $table = 'module';
    protected $primaryKey='mid';
    protected $fillable = ['mod_name','mod_url','interface_url','mod_pid','mod_path','mod_ico','mod_state','all_path','parent_name'];
    protected $dates = ['deleted_at'];


    public function roles(){
        return $this->belongsToMany('App\Role','role_module','rid','mid')->as('role_module')->withTimestamps();
    }

    //模块列表
    function lists(){
        $moduleList =  Module::where('is_show',1)->get();
        return $moduleList;
    }

    //模块添加/修改
    function add($mid,$fields){
        return $flag = Module::updateOrCreate(
            ['mid' => $mid],
            $fields
        );
    }

    //删除模块
    function del($mid)
    {
        $idArr = explode(',',$mid);
        foreach ($idArr as $key => $value){
            $flag = Module::find($value)->delete();
        }
        return $flag;
    }

    //路径查询模块
    function info($url){
        return Module::where('interface_url',$url)->first();
    }
}
