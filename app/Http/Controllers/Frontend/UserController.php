<?php

namespace App\Http\Controllers\Frontend;

use App\Activity;
use App\Region;
use App\User;
use App\Game;
use Illuminate\Http\Request;
use App\UserWeixin;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache as C;
use Illuminate\Support\Facades\Log;



class UserController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr = array('register', 'login', 'verifyCode', 'checkUsername', 'changePassword');
        parent::__construct($request);
    }

    //校验昵称是否重复
    public function checkUsername(Request $request)
    {
        $nickname = $request->input('nickname');
        $user = new User();
        if ($user->checkName($nickname)) {
            $msg['success'] = 0;
            $msg['msg'] = '昵称重复';
        } else {
            $msg['success'] = 1;
            $msg['msg'] = '昵称校验成功';
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        exit();
    }

    //判断手机验证码
    public function verifyCode(Request $request)
    {
        $this->validate($request, [
            'mobile' => 'required',
            'verify_code' => 'required',
        ]);
        $user = new User();
        $mobile = $request->input('mobile');
        $verifyCode = $request->input('verify_code');
        if ($user->checkVerifyCode($mobile, $verifyCode)) {
            $msg['success'] = 1;
            $msg['msg'] = '验证码验证成功';
        } else {
            $msg['success'] = 0;
            $msg['msg'] = '验证码不正确';
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        exit();
    }


    //用户注册
    public function register(Request $request)
    {
        $this->validate($request, [
            'mobile' => 'required',
            'nickname' => 'required',
            'sex' => 'required',
            'password' => 'required',
            'province' => 'required',
            'city' => 'required',
            'county' => 'required',
            'source' => 'required'
        ]);
        $mobile = $request->input('mobile');
        $nickname = $request->input('nickname');
        $password = $request->input('password');
        $source = $request->input('source');
        $unionid = $request->input('unionid');
        if ($source == 1) {
            $this->validate($request, [
                'unionid' => 'required',
            ]);
        }
        //判断用户是否已经存在
        $user = new User();
        $count = $user->isRegistered($mobile);
        if ($count > 0) {
            //手机已注册
            $msg['success'] = 0;
            $msg['msg'] = '您的手机已注册，请直接登录';
            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            exit();
        }
        //判断昵称是否重复
        if ($user->checkName($nickname)) {
            $msg['success'] = -1;
            $msg['msg'] = '昵称重复';
            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $fields = $request->all();
        foreach ($fields as $key => $value) {
            if ($key !== 'unionid') {
                $user->$key = $value;
            }
        }
        $user->password = md5($password);
        $msg['data'] = $user->save();
        $msg['success'] = 1;
        if ($source == 1) {
            $weixin = new UserWeixin();
            if ($weixin->is_exist($unionid)) {
                $userInfo = $user->getInfoByMobile($mobile)[0];
                $uid = $userInfo->id;
                $user->where('mobile', $mobile)->update(['last_ip' => $_SERVER["REMOTE_ADDR"], 'last_login' => date('Y-m-d H:i:s')]);
                $weixin->updateByUnionid($unionid, ['uid' => $uid]);
                $userAgent = $request->header('user_agent');
                $token = $user->createToken($userAgent, $msg['data'], $mobile);
                C::put($msg['data'], $token, 4320);
                if ($token) {
                    $msg['token'] = $token;
                    $msg['msg'] = '登录成功';
                } else {
                    $msg['success'] = -2;
                    $msg['msg'] = '生成token失败';
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                }
            } else {
                $msg['success'] = -3;
                $msg['msg'] = '缺少unionid';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
        } else {
            $msg['msg'] = '注册成功';
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        exit();
    }

    //登录后修改密码
    function loginChangePassword(Request $request)
    {
        $id = $this->userId;
        $this->validate($request, [
            'old_password' => 'required',
            'new_password' => 'required',
        ]);
        $user = new User();
        $oldPassword = $request->input('old_password');
        $newPassword = $request->input('new_password');
        //登录后修改
        $count = $user->where(['id' => $id, 'password' => md5($oldPassword)])->count();
        if (!$count) {
            $msg['success'] = -2;
            $msg['msg'] = '原密码不正确';
        } else {
            $user->where('id', $id)->update(['password' => md5($newPassword)]);
            $msg['success'] = 1;
            $msg['msg'] = '修改密码成功';
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        exit();
    }

    //忘记密码
    function changePassword(Request $request)
    {
        $this->validate($request, [
            'mobile' => 'required',
            'verify_code' => 'required',
            'password' => 'required',
        ]);
        $user = new User();
        $mobile = $request->input('mobile');
        $password = $request->input('password');
        $verifyCode = $request->input('verify_code');
        //判断用户是否锁定
        $status = $user->getUserStatus($mobile);
        if ($status == 0) {
            //锁定的账号
            if ($user->is_locked($mobile)) {
                $msg['msg'] = '账号锁定中';
                $msg['success'] = -1;
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                exit();
            }
        }
        if ($user->checkVerifyCode($mobile, $verifyCode)) {
            $user->where('mobile', $mobile)->update(['password' => md5($password)]);
            $msg['success'] = 1;
            $msg['msg'] = '修改密码成功';
        } else {
            $msg['success'] = 0;
            $msg['msg'] = '验证码不正确';
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        exit();
    }

    //用户登录
    function login(Request $request)
    {
        $this->validate($request, [
            'mobile' => 'required',
            'password' => 'required',
        ]);
        $user = new User();
        $mobile = $request->input('mobile');
        $password = $request->input('password');
        $count = User::where('mobile', $mobile)->count();
        if ($count == 0) {
            $msg['msg'] = '您的账号未注册';
            $msg['success'] = -4;
            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            exit();
        } else {
            //判断用户账号是否锁定
            $status = $user->getUserStatus($mobile);
            if ($status == 0) {
                //锁定的账号
                $info = $user->getInfoByMobile($mobile)[0];
                if (date('Y-m-d H:i:s', time() - 2 * 60 * 60) > $info->updated_at) {
                    //解锁
                    $user->changeUserStatus($mobile, 1);
                } else {
                    $msg['msg'] = '账号锁定中';
                    $msg['success'] = -3;
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    exit();
                }
            } elseif ($status == 2) {
                //停用
                $msg['msg'] = '账号已停用';
                $msg['success'] = -4;
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                exit();
            }
            $userInfo = User::where(['mobile' => $mobile, 'password' => md5($password)])->first();
            if (empty($userInfo) || $userInfo == null) {
                $user->setUserLogin($mobile, 0);
                $count = $user->getErrLoginCount($mobile);
                if ($count >= 5) {
                    $user->changeUserStatus($mobile, 0);
                    $msg['msg'] = '错误5次';
                    $msg['success'] = -2;
                } elseif ($count >= 3) {
                    $msg['msg'] = '错误3次';
                    $msg['success'] = -1;
                } else {
                    $msg['msg'] = '账号或密码不正确';
                    $msg['success'] = 0;
                }
            } else {
                User::where('id', $userInfo->id)
                    ->update(['last_ip' => $_SERVER["REMOTE_ADDR"], 'last_login' => date('Y-m-d H:i:s')]);
                $userAgent = $request->header('user_agent');
                $token = $user->createToken($userAgent, $userInfo->id, $mobile);
                C::put($userInfo->id, $token, 4320);
                $msg['data'] = $userInfo->id;
                $msg['success'] = 1;
                $msg['token'] = $token;
                $user->setUserLogin($mobile, 1);
            }
            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    //我的轨迹-获取游戏列表/活动列表
    public function userGameList(Request $request)
    {
        $userId = $this->userId;
        $user = new User();
        echo json_encode($user->userGameList($userId, 10), JSON_UNESCAPED_UNICODE);
    }

    //用户信息
    public function info(Request $request)
    {
        $id = $this->userId;
        $user = new User();
        $info = $user->getInfo($id);
        Log::info('test',['id'=>$id,'user'=>$info]);
        $userInfo['id'] = $id;
        $userInfo['nickname'] = $info->nickname??'';
        $userInfo['province'] = $info->province;
        $userInfo['city'] = $info->city;
        $userInfo['county'] = $info->county;
        $userInfo['sex'] = $info->sex;
        $userInfo['avatar'] = $info->avatar;
        $userInfo['rank'] = $user->userRank($id);
        $userInfo['score'] = $user->userScore($id);
        $region = new Region();
        $userInfo['region'] = $region->regionName($info->province . ',' . $info->city . ',' . $info->county);
        $userInfo['success'] = 1;
        echo json_encode($userInfo, JSON_UNESCAPED_UNICODE);
    }

    //修改信息
    public function editInfo(Request $request)
    {
        $id = $this->userId;
        $nickname = $request->input('nickname');
        $user = new User();
        if ($user->checkName($nickname, $id)) {
            $msg['success'] = 0;
            $msg['msg'] = '昵称重复';
            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $user = User::find($id)->update($request->all());
        if ($user) {
            $msg['success'] = 1;
            $msg['data'] = $user;
            $msg['msg'] = '修改成功';

        } else {
            $msg['success'] = -1;
            $msg['msg'] = '修改失败';
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //上传头像
    public function uploadAvatar(Request $request)
    {
        $path = $request->file('avatar')->store('uploads');
        if ($path) {
            $msg['success'] = 1;
            $msg['path'] = asset($path);

        } else {
            $msg['success'] = 0;
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //用户获得积分
    public function addScore(Request $request)
    {
        $this->validate($request, [
            'app_id' => 'required',
            'score' => 'required',
            'sign' => 'required',
            //'game_id'=>'required'
        ]);
        $sign = $request->input('sign');
        $score = $request->input('score');
        $appId = $request->input('app_id');
        $game = Game::where('appid', $appId)->get()[0];
        $gameId = $game->id;
        $appSecret = $game->appsecret;
        $token = $request->header('token');
        $md5String = md5($appId . $appSecret . $score . $token);
        if ($md5String == $sign) {
            $userId = $this->userId;
            $user = User::with('games')->find($userId);
            $userGame = $user->games()->attach($gameId, ['score' => $score]);
            if ($user->games()) {
                $msg['success'] = 1;
                $msg['msg'] = '添加积分成功';
            } else {
                $msg['success'] = 0;
                $msg['msg'] = '添加积分失败';
            }
        } else {
            $msg['success'] = -1;
            $msg['msg'] = '校验失败';
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        exit();
    }

    //参与活动
    function attendActivity(Request $request)
    {
        $this->validate($request, [
            'app_id' => 'required',
            //'activity_id' => 'required',
            'sign' => 'required',
            'step' => 'required',
            'step_value' => 'required',
        ]);
        $appId = $request->input('app_id');
        $userId = $this->userId;
        //$activityId = $request->input('activity_id');
        $step = $request->input('step');
        $stepValue = $request->input('step_value');
        $sign = $request->input('sign');
        $activity = Activity::where('appid', $appId)->get()[0];
        $activityId = $activity->id;
        $appSecret = $activity->appsecret;
        $token = $request->header('token');
        $md5String = md5($appId . $appSecret . $step . $stepValue . $token);
        if ($md5String == $sign) {
            $user = User::with('activities')->find($userId);
            $userActivity = $user->activities()->attach($activityId, ['step' => $step, 'step_value' => $stepValue]);
            if ($user->activities()) {
                $msg['success'] = 1;
                $msg['msg'] = '参与活动成功';
            } else {
                $msg['success'] = 0;
                $msg['msg'] = '参与活动失败';
            }
        } else {
            $msg['success'] = -1;
            $msg['msg'] = '校验失败';
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        exit();
    }

    //用户游戏积分列表
    public function gameScoreList()
    {
        $userId = $this->userId;
        $game = new Game();
        $user = new User();
        $scoreList = \DB::select('SELECT game_id,SUM(score) as score FROM user_game GROUP BY game_id,user_id HAVING user_id= ? ORDER BY SUM(score) DESC', [$userId]);
        $score = 0;
        $i = 0;
        $UndeletedIdList = \DB::select('SELECT id FROM game WHERE deleted_at IS NULL');
        $UndeletedIdArr = array();
        foreach ($UndeletedIdList as $k => $v) {
            $UndeletedIdArr[] = $v->id;
        }
        if (!empty($scoreList)) {
            foreach ($scoreList as $key => $value) {
                if (in_array($value->game_id, $UndeletedIdArr)) {
                    $userScoreList[$i]['game_id'] = $value->game_id;
                    $userScoreList[$i]['score'] = $value->score;
                    $score += $value->score;
                    $game = Game::find($value->game_id);
                    $userScoreList[$i]['picture'] = $game->picture;
                    $userScoreList[$i]['name'] = $game->name;
                    $i++;
                }
            }
            $arr['scoreList'] = $userScoreList;
            $arr['rank'] = $user->userRank($userId);
            $arr['total_score'] = $score;
        } else {
            $arr['scoreList'] = array();
            $arr['rank'] = 0;
            $arr['score'] = 0;
        }
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    }

    //总排行榜-获取地区人数总榜-自己的排行
    function userRankRegionTotal(Request $request)
    {
        $userId = $this->userId;
        $user = new User();
        $region = new Region();
        $info = $user->getInfo($userId);
        $userRank = array();
        $totalList = Redis::Zrevrange('regionTotalRanking', 0, -1, 'WITHSCORES');
        $i = 0;
        $regionName = $region->getFullName($info->county);
        foreach ($totalList as $r => $total) {
            $i++;
            if ($r == $regionName) {
                $userRank[0]['rank'] = $i;
                $userRank[0]['region'] = $regionName;
                $userRank[0]['total'] = $total;
                break;
            }
        }
        return json_encode($userRank,JSON_UNESCAPED_UNICODE)??array('');
    }

    //总排行榜-获取地区分数总榜-自己的排行
    function userRankRegionScore(Request $request)
    {
        $userId = $this->userId;
        $user = new User();
        $region = new Region();
        $info = $user->getInfo($userId);
        $userRank = array();
        $scoreList = Redis::Zrevrange('regionTotalRanking', 0, -1, 'WITHSCORES');
        $i = 0;
        $regionName = $region->getFullName($info->county);
        foreach ($scoreList as $r => $score) {
            $i++;
            if ($r == $regionName) {
                $userRank[0]['rank'] = $i;
                $userRank[0]['region'] = $regionName;
                $userRank[0]['score'] = $score;
                break;
            }
        }
        return json_encode($userRank,JSON_UNESCAPED_UNICODE)??array('');
    }

    //总排行榜-获取用户分数总榜-自己的排行
    function userRankUserScore(Request $request)
    {
        $userId = $this->userId;
        $user = new User();
        $info = $user->getInfo($userId);
        $userRank = array();
        $scoreList = Redis::Zrevrange('userScoreRanking', 0, -1, 'WITHSCORES');
        $i = 0;
        foreach ($scoreList as $id => $score) {
            $i++;
            if ($id == $userId) {
                $userRank[0]['rank'] = $i;
                $userRank[0]['avatar'] = $info->avatar;
                $userRank[0]['sex'] = $info->sex;
                $userRank[0]['nickname'] = $info->nickname;
                $userRank[0]['score'] = $score;
                break;
            }
        }
        return json_encode($userRank,JSON_UNESCAPED_UNICODE)??array('');
    }

    //游戏排行榜-获取用户分数榜-自己的排行
    function userRankScore(Request $request)
    {
        $userId = $this->userId;
        $user = new User();
        $info = $user->getInfo($userId);
        $userRank = array();
        $gameId = $request->input('game_id');
        $ranking = Redis::Zrevrange('gameUserScoreRanking_' . $gameId, 0, -1, 'WITHSCORES');
        $i = 0;
        foreach ($ranking as $id => $score) {
            $i++;
            if ($id == $userId) {
                $userRank[0]['rank'] = $i;
                $userRank[0]['avatar'] = $info->avatar;
                $userRank[0]['sex'] = $info->sex;
                $userRank[0]['nickname'] = $info->nickname;
                $userRank[0]['score'] = $score;
                break;
            }
        }
        return json_encode($userRank,JSON_UNESCAPED_UNICODE)??array('');
    }

    //游戏排行榜-获取地区人数榜-自己的排行
    function userRankGameRegion(Request $request){
        $userId = $this->userId;
        $gameId = $request->input('game_id');
        $user = new User();
        $region = new Region();
        $info = $user->getInfo($userId);
        $userRank = array();
        $totalList = Redis::Zrevrange('gameRegionTotalRanking_' . $gameId, 0, -1, 'WITHSCORES');
        $i = 0;
        $regionName = $region->getFullName($info->county);
        foreach ($totalList as $r => $total) {
            $i++;
            if ($r == $regionName) {
                $userRank[0]['rank'] = $i;
                $userRank[0]['region'] = $regionName;
                $userRank[0]['total'] = $total;
                break;
            }
        }
        return json_encode($userRank,JSON_UNESCAPED_UNICODE)??array('');
    }

    //游戏排行榜-获取地区分数榜-自己的排行
    function userRankGameScore(Request $request){
        $gameId = $request->input('game_id');
        $userId = $this->userId;
        $user = new User();
        $region = new Region();
        $info = $user->getInfo($userId);
        $userRank = array();
        $scoreList = Redis::Zrevrange('$gameRegionScoreRanking_' . $gameId, 0, -1, 'WITHSCORES');
        $i = 0;
        $regionName = $region->getFullName($info->county);
        foreach ($scoreList as $r => $score) {
            $i++;
            if ($r == $regionName) {
                $userRank[0]['rank'] = $i;
                $userRank[0]['region'] = $regionName;
                $userRank[0]['score'] = $score;
                break;
            }
        }
        return json_encode($userRank,JSON_UNESCAPED_UNICODE)??array('');
    }
}
