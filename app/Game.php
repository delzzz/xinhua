<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use SoftDeletes;
    protected $table = 'game';
    protected $fillable = ['name','picture','url','level','status','appid','appsecret','description','uid','tag_id'];
    protected $dates = ['deleted_at'];

    //用户
    function users(){
        return $this->belongsToMany('App\User','user_game','game_id','user_id')->as('user_game')->withTimestamps();
    }

    //游戏列表
    function getList(){
        return Game::where('status',1)->get();
    }

    //游戏id获取详情
    function getInfo($gameId){
        return Game::withTrashed()->find($gameId);
    }

    //游戏appid获取详情
    function getInfoByAppId($appId){
        return Game::withTrashed()->where('appid',$appId)->get();
    }

    //删除游戏
    function del($id){
        $idArr = explode(',',$id);
        foreach ($idArr as $key => $value){
            $flag = Game::find($value)->delete();
        }
        return $flag;
    }

    //改变状态
    function changeStatus($id, $status)
    {
        return Game::find($id)->update(['status' => $status]);
    }

    //判断appid是否重复
    function checkAppID($appId,$id = null){
        if (!empty($id)) {
            $count = Game::where('appid', $appId)->where('id', '<>', $id)->count();
        } else {
            $count = Game::where('appid', $appId)->count();
        }
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    //活动专题设置游戏信息
    function setGameInfo($itemId,$topicId){
        $gameInfo = $this->getInfo($itemId);
        $arr['topic_id'] = $topicId;
        $arr['itemid'] = $itemId;
        $arr['name'] = $gameInfo->name;
        $arr['picture'] = $gameInfo->picture;
        $arr['type'] = 'game';
        $arr['status'] = $gameInfo->status;
        $arr['url'] = $gameInfo->url;
        $arr['tag_id'] = $gameInfo->tag_id;
        $arr['description'] = $gameInfo->description;
        return $arr;
    }
}
