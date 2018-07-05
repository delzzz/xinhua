<?php

namespace App\Http\Controllers\Frontend;

use App\User;
use App\UserWeixin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache as C;


class WeixinController extends Controller
{

    //微信页面 code获取access_token返回信息/登录
    function getInfo(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
        ]);
        $code = $request->input('code');
        $weixin = new UserWeixin();
        $appId = env('APP_ID');
        $appSecret = env('APP_SECRET');
        $arr = $weixin->get_access_token($appId, $appSecret, $code);
        try {
            $unionid = $arr['unionid'];
        } catch (\Exception $e) {
            $msg['success'] = -2;
            $msg['msg'] = 'code有误';
            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $infoArr = $weixin->get_user_info($arr['access_token'], $arr['openid']);
        $weixin = new UserWeixin();
        $userInfo['access_token'] = $arr['access_token'];
        $userInfo['refresh_token'] = $arr['refresh_token'];
        $userInfo['scope'] = $arr['scope'];
        $userInfo['expires_in'] = $arr['expires_in'];
        $userInfo['headimgurl'] = $infoArr['headimgurl'];
        $userInfo['nickname'] = $infoArr['nickname'];
        $userInfo['sex'] = $infoArr['sex'];
        $userInfo['country'] = $infoArr['country'];
        $userInfo['province'] = $infoArr['province'];
        $userInfo['city'] = $infoArr['city'];
        $userInfo['openid'] = $infoArr['openid'];
        //根据unionid判断用户是否存在
        if ($weixin->is_exist($unionid)) {
            $weixin->updateByUnionid($unionid, $userInfo);
            //判断有没有uid
            $uid = $weixin->is_regitered($unionid);
            if ($uid) {
                //判断账号是否锁住
                $user = new User();
                $uInfo = $user->getInfo($uid);
                if ($uInfo->status == 2) {
                    $msg['msg'] = '账号已停用';
                    $msg['success'] = -1;
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    exit();
                }
                //登录
                $userAgent = $request->header('user_agent');
                $user->where('id', $uid)->update(['last_ip' => $_SERVER["REMOTE_ADDR"], 'last_login' => date('Y-m-d H:i:s')]);
                $token = $user->createToken($userAgent, $uid, $uInfo->mobile);
                $msg['token'] = $token;
                $msg['success'] = 1;
                $msg['msg'] = '登录成功';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                exit();
            }
        } else {
            //插入微信数据
            $userInfo['unionid'] = $unionid;
            $weixin->insertInfo($userInfo);
        }
        $weixinArr['base'] = $arr;
        $weixinArr['info'] = $infoArr;
        echo json_encode($weixinArr, JSON_UNESCAPED_UNICODE);
        exit();
    }


    /**
     * 处理微信的请求消息
     *
     * @return string
     */

    function test()
    {
        $app = app('wechat.official_account');
        $app->server->push(function ($message) {
            return "欢迎关注 overtrue！";
        });
        return $app->server->serve();
    }

    public function serve(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
        ]);
        $code = $request->input('code');
        $weixin = new UserWeixin();
        $appId = env('OFFICIAL_APP_ID');
        $appSecret = env('OFFICIAL_APP_SECRET');
        $arr = $weixin->get_access_token($appId, $appSecret, $code);
        try {
            $infoArr = $weixin->get_user_info($arr['access_token'], $arr['openid']);
        } catch (\Exception $e) {
            $msg['success'] = -3;
            $msg['msg'] = 'code有误';
            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            exit();
        }
        $weixin = new UserWeixin();
        $userInfo['access_token'] = $arr['access_token'];
        $userInfo['refresh_token'] = $arr['refresh_token'];
        $userInfo['scope'] = $arr['scope'];
        $userInfo['expires_in'] = $arr['expires_in'];
        $userInfo['headimgurl'] = $infoArr['headimgurl'];
        $userInfo['nickname'] = $infoArr['nickname'];
        $userInfo['sex'] = $infoArr['sex'];
        $userInfo['country'] = $infoArr['country'];
        $userInfo['province'] = $infoArr['province'];
        $userInfo['city'] = $infoArr['city'];
        $userInfo['openid'] = $infoArr['openid'];
        try {
            $unionid = $infoArr['unionid'];
        } catch (\Exception $e) {
            $msg['success'] = -2;
            $msg['msg'] = '没有unionid';
            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            exit();
        }
        //根据unionid判断用户是否存在
        if ($weixin->is_exist($unionid)) {
            $weixin->updateByUnionid($unionid, $userInfo);
            //判断有没有uid
            $uid = $weixin->is_regitered($unionid);
            if ($uid) {
                //判断账号是否锁住
                $user = new User();
                $uInfo = $user->getInfo($uid);
                if ($uInfo->status == 2) {
                    $msg['msg'] = '账号已停用';
                    $msg['success'] = -1;
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    exit();
                }
                //登录
                $userAgent = $request->header('user_agent');
                $user->where('id', $uid)->update(['last_ip' => $_SERVER["REMOTE_ADDR"], 'last_login' => date('Y-m-d H:i:s')]);
                $token = $user->createToken($userAgent, $uid, $uInfo->mobile);
                $msg['token'] = $token;
                $msg['success'] = 1;
                $msg['msg'] = '登录成功';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                exit();
            }
        } else {
            //插入微信数据
            $userInfo['unionid'] = $unionid;
            $weixin->insertInfo($userInfo);
        }
        $weixinArr['base'] = $arr;
        $weixinArr['info'] = $infoArr;
        echo json_encode($weixinArr, JSON_UNESCAPED_UNICODE);
        exit();

    }

    function getSign(Request $request)
    {
        $this->validate($request, [
            'url' => 'required',
        ]);
        $url = $request->input('url');
        $weixin = new UserWeixin();
        $appId = env('OFFICIAL_APP_ID');
        $appSecret = env('OFFICIAL_APP_SECRET');
        $access_token = C::get('access_token');
        if($access_token){
            $accessToken = $access_token;
        }
        else{
            $info = $weixin->js_get_access_token($appId, $appSecret);
            $accessToken = $info['access_token'];
            C::put('access_token',$accessToken,'10');
        }
        $jsapi_ticket = C::get('jsapi_ticket');
        if($jsapi_ticket){
            $jsapiTicket = $jsapi_ticket;
        }
        else{
            $jsInfo = $weixin->js_get_sign($accessToken);
            $jsapiTicket = $jsInfo['ticket'];
            C::put('jsapi_ticket',$jsapiTicket,'10');
        }
        $randStr = rand(10000, 99999);
        $timeStamp = time();
        //$url = 'http://wxcb3.test.qyuedai.com/';
        $jsStr = 'jsapi_ticket=' . $jsapiTicket . '&noncestr=' . $randStr . '&timestamp=' . $timeStamp . '&url=' . $url;
        $signature = sha1($jsStr);
        $arr['appid'] = $appId;
        $arr['jsapi_ticket'] = $jsapiTicket;
        $arr['noncestr'] = $randStr;
        $arr['timestamp'] = $timeStamp;
        $arr['signature'] = $signature;
        return  json_encode($arr, JSON_UNESCAPED_UNICODE);
    }


}
