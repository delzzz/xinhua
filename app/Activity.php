<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;
    protected $table = 'activity';
    protected $fillable = ['name', 'picture', 'url', 'level', 'status', 'appid', 'appsecret', 'description', 'uid','tag_id'];
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

    //活动专题设置h5链接id
    function setActivityInfo($itemId,$topicId){
        $activityInfo = $this->getInfo($itemId);
        $arr['topic_id'] = $topicId;
        $arr['itemid'] = $itemId;
        $arr['name'] = $activityInfo->name;
        $arr['picture'] = $activityInfo->picture;
        $arr['type'] = 'activity';
        $arr['status'] = $activityInfo->status;
        $arr['url'] = $activityInfo->url;
        $arr['tag_id'] = $activityInfo->tag_id;
        $arr['description'] = $activityInfo->description;
        return $arr;
    }
}
