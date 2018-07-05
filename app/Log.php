<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'admin_log';
    protected $primaryKey='id';
    public $timestamps = false;
    protected $fillable = ['uid','description','result','type','created_at'];

    //日志列表
    function lists(){
        return Log::all();
    }

    //添加日志
    function addLog($uid,$description,$status,$type){
        Log::create(['uid'=>$uid,'description'=>$description,'result'=>$status,'type'=>$type,'created_at'=>date('Y-m-d H:i:s')]);
    }

}
