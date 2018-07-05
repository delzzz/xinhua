<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;
    protected $table = 'article';
    protected $primaryKey='aid';
    protected $fillable = ['title','picture', 'url', 'content','click','uid','remark'];
    protected $dates = ['deleted_at'];


    //文章列表
    function lists(){
       return Article::orderBy('created_at','desc')->get();
    }

    //增加点击次数
    function addClick($aid){
        return Article::where('aid',$aid)->increment('click',1);
    }

    //详情
    function getInfo($aid){
        return Article::find($aid);
    }

    //删除文章
    function del($id){
        $idArr = explode(',',$id);
        foreach ($idArr as $key => $value){
            $flag = Article::find($value)->delete();
        }
        return $flag;
    }
}
