<?php

namespace App\Http\Controllers\Backend;

use App\Activity;
use App\Region;
use App\User;
use App\Game;
use App\GetIP;
use App\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class UserController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr = array('userList','userInfo');
        parent::__construct($request);
    }

    //改变用户状态
    function  changeUserStatus(Request $request){
        $this->validate($request, [
            'id' => 'required',
            'status' => 'required'
        ]);
        $id = $request->input('id');
        $status = $request->input('status');
        $user = new User();
        $info = $user->getInfo($id);
        if($status==1){
            $description = '启用用户'.$info->nickname;
        }
        else{
            $description = '禁用用户'.$info->nickname;
        }
        $user = User::find($id);
        $user->status = $status;
        $flag = $user->save();
        if($flag){
            $msg['success'] = 1;
            $msg['msg'] = $description.'成功';
            $type = 1;
        }
        else{
            $msg['success'] = 0;
            $msg['msg'] = $description.'失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId,$description,$msg['success'],$type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //用户列表
    function lists(Request $request){
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $key = $request->input('key');
        $user = new User();
        $users = $user->where(function ($query) use ($key) {
            $key && $query->where('mobile', 'like', '%' . $key . '%')->orWhere('nickname', 'like', '%' . $key . '%');
        })->paginate($perPage, ['id', 'mobile','sex', 'nickname', 'avatar', 'last_ip', 'status','county'], 'p', $p);
        $region = new Region();
        $userScore = Redis::Zrange('userScoreRanking', 0, -1, 'WITHSCORES');
        //$getip = new GetIP();
        foreach ($users as $key => $value) {

            //$ipArr = $getip->lookUp($value->last_ip);
//            if($ipArr){
//                $userList[$key]['last_ip'] = $ipArr['province'].$ipArr['city'];
//            }
//            else{
//                $userList[$key]['last_ip'] = '';
//            }
            $users[$key]['region'] = $region->getFullName($value->county);
            $users[$key]['score'] = $userScore[$value->id]??0;
        }
        return json_encode($users, JSON_UNESCAPED_UNICODE);
    }

    //用户详情
    function info(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);
        $id = $request->input('id');
        $user = new User();
        $info = $user->getInfo($id);
        $userInfo['id'] = $id;
        $userInfo['mobile'] = $info->mobile;
        $userInfo['nickname'] = $info->nickname;
        $userInfo['province'] = $info->province;
        $userInfo['city'] = $info->city;
        $userInfo['source'] = $info->source;
        $region = new Region();
        $userInfo['region'] = $region->getFullName($info->county);
        $userInfo['sex'] = $info->sex;
        $userInfo['avatar'] = $info->avatar;
        $userScore = Redis::Zrange('userScoreRanking', 0, -1, 'WITHSCORES');
        $userInfo['score'] = $userScore[$id]??0;
        $userInfo['created_at'] = $info->created_at->toDateTimeString();
        $userDetail['info'] = $userInfo;
        $userDetail['history'] = $user->userGameList($id,5);
        return json_encode($userDetail,JSON_UNESCAPED_UNICODE);
    }

    //删除用户
    function del(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);
        $id = $request->input('id');
        $user = new User();
        $info = $user->getInfo($id);
        $description = '删除用户'.$info->name;
        if($user->del($id)){
            $msg['success'] = 1;
            $msg['msg'] = $description.'成功';
            $type = 1;
        }
        else{
            $msg['success'] = 0;
            $msg['msg'] = $description.'失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId,$description,$msg['success'],$type);
        return json_encode($msg,JSON_UNESCAPED_UNICODE);
    }

    //修改密码
    function editPassword(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'password' => 'required',
        ]);
        $id = $request->input('id');
        $password = $request->input('password');
        $user = new User();
        $info = $user->getInfo($id);
        $description = '修改用户'.$info->nickname.'密码';
        if ($user->editPassword($id, $password)) {
            $msg['success'] = 1;
            $msg['msg'] = $description.'成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description.'修改失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId,$description,$msg['success'],$type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }
}
