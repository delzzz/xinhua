<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache as C;


class User extends Model
{
    use SoftDeletes;
    protected $table = 'user';
    protected $fillable = ['id', 'nickname', 'status', 'province', 'city', 'county', 'avatar', 'last_ip', 'last_login', 'deleted_at', 'sex', 'source'];
    protected $dates = ['deleted_at'];
    protected $hidden = ['password'];

    //游戏
    function games()
    {
        return $this->belongsToMany('App\Game', 'user_game', 'user_id', 'game_id')->as('user_game')->withPivot('score')->withTimestamps();
    }

    //活动
    function activities()
    {
        return $this->belongsToMany('App\Activity', 'user_activity', 'user_id', 'activity_id')->as('user_activity')->withPivot('step', 'step_value')->withTimestamps();
    }

    //根据userId获取用户信息
    function getInfo($userId)
    {
        $userInfo = User::find($userId);
        return $userInfo;
    }

    //判断用户是否注册
    function isRegistered($mobile)
    {
        $count = User::where('mobile', $mobile)->count();
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    //根据手机号获取用户信息
    function getInfoByMobile($mobile)
    {
        return User::where('mobile', $mobile)->get();
    }

    //用户登录
    function userLogin($lastIP,$userId,$userAgent,$mobile){
        $user = new User();
        User::where('id', $userId)
            ->update(['last_ip' => $lastIP, 'last_login' => date('Y-m-d H:i:s')]);
        $token = $user->createToken($userAgent, $userId, $mobile);
        C::put($userId, $token, 4320);
        $user->setUserLogin($mobile, 1);
        return $token;
    }

    //用户登录日志
    function setUserLogin($mobile, $status)
    {
        \DB::table('user_login')->insert(['mobile' => $mobile, 'ip_addr' => $_SERVER["REMOTE_ADDR"], 'login_time' => date('Y-m-d H:i:s', time()), 'status' => $status]);
    }

    //获取用户登录错误次数
    function getErrLoginCount($mobile)
    {
        $todayStart = date('Y-m-d H:i:s', time() - 60 * 10);
        $todayEnd = date('Y-m-d H:i:s');
        $count = \DB::table('user_login')
            ->where('mobile', $mobile)
            ->where('status', 1)
            ->whereBetween('login_time', [$todayStart, $todayEnd])
            ->count();
        return $count;
    }

    //更改用户状态
    function changeUserStatus($mobile, $status)
    {
        User::where('mobile', $mobile)->update(['status' => $status]);
    }

    //获取用户状态
    function getUserStatus($mobile)
    {
        $info = User::where('mobile', $mobile)->get()[0];
        return $info->status;
    }

    //检查昵称是否重复
    function checkName($nickname, $uid = null)
    {
        if (!empty($uid)) {
            $count = User::where('nickname', $nickname)->where('id', '<>', $uid)->count();
        } else {
            $count = User::where('nickname', $nickname)->count();
        }
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    //手机号验证码
    function checkVerifyCode($mobile, $verifyCode)
    {
        $currentTime = date('Y-m-d H:i:s');
        $verify_code = C::get($mobile);
        if ($verify_code == $verifyCode) {
            return true;
        } else {
            return false;
        }
    }

    //用户轨迹
    function userGameList($userId, $num)
    {
        $userGameList = array();
        $userActivityList = array();
        //游戏列表
        $gameList = Redis::Zrange('gameTotalRanking', 0, -1, 'WITHSCORES');
        $usersGames = \DB::select('SELECT name,game_id,user_id,game.status,MAX(user_game.created_at) AS max_created,picture,url FROM user_game 
INNER JOIN game ON game.id = user_game.`game_id`
GROUP BY game_id,user_id,picture,name,deleted_at,status,url 
HAVING user_id=? and deleted_at is null ORDER BY max_created DESC limit ' . $num, [$userId]);
        foreach ($usersGames as $key => $value) {
            $userGameList[$key]['id'] = $value->game_id;
            $userGameList[$key]['picture'] = $value->picture;
            $userGameList[$key]['total'] = $gameList[$value->game_id]??1;
            $userGameList[$key]['type'] = 'game';
            $userGameList[$key]['created_at'] = $value->max_created;
            $userGameList[$key]['name'] = $value->name;
            $userGameList[$key]['status'] = $value->status;
            $userGameList[$key]['url'] = $value->url;
        }
        //活动列表
        $activityList = Redis::Zrange('activityTotalRanking', 0, -1, 'WITHSCORES');
        $userActivities = \DB::select('SELECT name,activity_id,user_id,activity.status,MAX(user_activity.created_at) AS max_created,picture FROM user_activity 
INNER JOIN activity ON activity.id = user_activity.`activity_id`
GROUP BY activity_id,user_id,picture,name,deleted_at,status 
HAVING user_id=? and deleted_at is null ORDER BY max_created DESC limit ' . $num, [$userId]);
        foreach ($userActivities as $key => $value) {
            $userActivityList[$key]['id'] = $value->activity_id;
            $userActivityList[$key]['picture'] = $value->picture;
            $userActivityList[$key]['total'] = $activityList[$value->activity_id];
            $userActivityList[$key]['type'] = 'activity';
            $userActivityList[$key]['created_at'] = $value->max_created;
            $userActivityList[$key]['name'] = $value->name;
            $userActivityList[$key]['status'] = $value->status;
        }
        $mergeArr = array_merge($userGameList, $userActivityList);
        array_multisort(array_column($mergeArr, 'created_at'), SORT_DESC, $mergeArr);
        return array_slice($mergeArr, 0, $num);
    }

    //删除用户
    function del($id)
    {
        $idArr = explode(',', $id);
        $weixin = new UserWeixin();
        foreach ($idArr as $key => $value) {
            $info = User::find($value);
            //if ($info->source == 1) {
                $weixin->where('uid', $value)->delete();
            //}
            $flag = User::find($value)->delete();
        }
        return $flag;
    }

    //用户排行
    function userRank($id)
    {
        if(empty(Redis::Zrevrank('userScoreRanking', $id))){
            return 0;
        }
        return Redis::Zrevrank('userScoreRanking', $id) + 1;
    }

    //用户积分
    function userScore($id)
    {
        $scoreList = \DB::select('SELECT SUM(score) as score FROM user_game where user_id= ? ', [$id]);
        return $scoreList[0]->score;
    }

    //生成token
    function createToken($userAgent, $userId, $mobile)
    {
        $tokenStr = $userAgent . '|' . json_encode(array('user_id' => $userId, 'mobile' => $mobile)) . '|' . env('APP_KEY');
        $token = base64_encode($tokenStr);
        return $token;
    }

    //锁定账号是否解锁
    function is_locked($mobile)
    {
        //锁定的账号
        $info = $this->getInfoByMobile($mobile)[0];
        if (date('Y-m-d H:i:s', time() - 2 * 60 * 60) > $info->updated_at) {
            //解锁
            $this->changeUserStatus($mobile, 1);
            return false;
        } else {
            return true;
        }
    }

    //用户来源
    function userSource()
    {
        //总用户
        $total = User::where(['deleted_at' => null])->count();
        //普通用户
        $mobilelUser = User::where(['deleted_at' => null, 'source' => 0])->count();
        //微信用户
        $wechatlUser = User::where(['deleted_at' => null, 'source' => 1])->count();
        $userSource[0]['total'] = $total;
        $userSource[0]['mobile'] = $mobilelUser;
        $userSource[0]['wechat'] = $wechatlUser;
        return $userSource;
    }

    //后台修改密码
    function editPassword($id,$password){
        $user = User::find($id);
        $user->password = md5($password);
        return $user->save();
    }

}
