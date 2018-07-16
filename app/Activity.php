<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;
    protected $table = 'activity';
    protected $fillable = ['name', 'picture', 'url', 'level', 'status', 'appid', 'appsecret', 'description', 'uid'];
    protected $dates = ['deleted_at'];

    //用户
    public function users()
    {
        return $this->belongsToMany('App\User', 'user_activity', 'activity_id', 'user_id')->as('user_activity')->withTimestamps();
    }

    //改变状态
    function changeStatus($id, $status)
    {
        return Activity::find($id)->update(['status' => $status]);
    }

    //删除
    function del($id)
    {
        $idArr = explode(',', $id);
        foreach ($idArr as $key => $value) {
            $flag = Activity::find($value)->delete();
        }
        return $flag;
    }

    //活动详情
    function getInfo($id){
        return Activity::find($id);
    }

    //专题-活动列表
    public function getList(){
        return Activity::where('status',1)->get();
    }

    //判断appid是否重复
    function checkAppID($appId,$id = null){
        if (!empty($id)) {
            $count = Activity::where('appid', $appId)->where('id', '<>', $id)->count();
        } else {
            $count = Activity::where('appid', $appId)->count();
        }
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }
}
