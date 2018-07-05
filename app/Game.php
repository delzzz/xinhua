<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use SoftDeletes;
    protected $table = 'game';
    protected $fillable = ['name','picture','url','level','status','appid','appsecret','description','uid'];
    protected $dates = ['deleted_at'];

    //用户
    public function users(){
        return $this->belongsToMany('App\User','user_game','game_id','user_id')->as('user_game')->withTimestamps();
    }

    //游戏列表
    public function getList(){
        return Game::where('status',1)->get();
    }

    //游戏详情
    public function getInfo($gameId){
        return Game::withTrashed()->find($gameId);
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
}
