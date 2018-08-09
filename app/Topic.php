<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Topic extends Model
{
    use SoftDeletes;
    protected $table = 'topic';
    protected $fillable = ['name', 'description', 'url', 'level','click','is_link','uid','picture','status'];
    protected $dates = ['deleted_at'];

    //活动列表
    function lists(){
        return Topic::where(['level'=>'asc','status'=>1])->get();
    }

    //删除活动
    function del($id)
    {
        $idArr = explode(',',$id);
        foreach ($idArr as $key => $value){
            Topic::find($value)->delete();
            $flag = TopicLink::where('topic_id',$value)->delete();
        }
        return $flag;
    }

    //详情
    function info($topicId){
        return Topic::find($topicId);
    }

    //增加点击次数
    function addClick($topicId){
        return Topic::where('id',$topicId)->increment('click',1);
    }
}
